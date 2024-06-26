<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace CartThrob\Dependency\Symfony\Component\HttpFoundation\Session\Storage\Handler;

/**
 * Adds basic `SessionUpdateTimestampHandlerInterface` behaviors to another `SessionHandlerInterface`.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class StrictSessionHandler extends AbstractSessionHandler
{
    private \SessionHandlerInterface $handler;
    private bool $doDestroy;
    public function __construct(\SessionHandlerInterface $handler)
    {
        if ($handler instanceof \SessionUpdateTimestampHandlerInterface) {
            throw new \LogicException(\sprintf('"%s" is already an instance of "SessionUpdateTimestampHandlerInterface", you cannot wrap it with "%s".', \get_debug_type($handler), self::class));
        }
        $this->handler = $handler;
    }
    public function open(string $savePath, string $sessionName) : bool
    {
        parent::open($savePath, $sessionName);
        return $this->handler->open($savePath, $sessionName);
    }
    /**
     * {@inheritdoc}
     */
    protected function doRead(string $sessionId) : string
    {
        return $this->handler->read($sessionId);
    }
    public function updateTimestamp(string $sessionId, string $data) : bool
    {
        return $this->write($sessionId, $data);
    }
    /**
     * {@inheritdoc}
     */
    protected function doWrite(string $sessionId, string $data) : bool
    {
        return $this->handler->write($sessionId, $data);
    }
    public function destroy(string $sessionId) : bool
    {
        $this->doDestroy = \true;
        $destroyed = parent::destroy($sessionId);
        return $this->doDestroy ? $this->doDestroy($sessionId) : $destroyed;
    }
    /**
     * {@inheritdoc}
     */
    protected function doDestroy(string $sessionId) : bool
    {
        $this->doDestroy = \false;
        return $this->handler->destroy($sessionId);
    }
    public function close() : bool
    {
        return $this->handler->close();
    }
    public function gc(int $maxlifetime) : int|false
    {
        return $this->handler->gc($maxlifetime);
    }
}
