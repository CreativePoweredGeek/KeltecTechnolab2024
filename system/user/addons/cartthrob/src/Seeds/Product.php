<?php

namespace CartThrob\Seeds;

use CartThrob\Seeder\Channels\AbstractField;
use CartThrob\Seeder\Channels\Entries\Entry as ChannelEntry;
use CartThrob\Seeder\Core\AbstractSeed;
use CartThrob\Seeder\Core\SeedInterface;
use CartThrob\Seeder\Exceptions\Seeds\InvalidChannelException;
use CartThrob\Seeder\Seeds\Entry as EntrySeed;

class Product extends EntrySeed
{
    /**
     * @var string
     */
    protected string $type = 'cartthrob/product';

    public function __construct()
    {
        ee()->load->add_package_path(PATH_THIRD . 'cartthrob/');
        ee()->load->library('cartthrob_loader');
    }

    /**
     * @return AbstractSeed
     * @throws \CartThrob\Seeder\Exceptions\Channels\Entries\EntryException
     * @throws \CartThrob\Seeder\Exceptions\Seeds\InvalidChannelException
     */
    public function generate(): AbstractSeed
    {
        if ($this->option('channel') == '') {
            throw new InvalidChannelException('Channel is required!');
        }

        $product_channel_map = ee()->config->item('cartthrob:product_channel_fields');
        $cd_service = ee('cartthrob_seeder:ChannelEntryService');
        $entry = $cd_service->getBlankEntry($this->getChannelid());
        if ($entry instanceof ChannelEntry) {
            $entry->set('title', ucwords(implode(' ', $this->faker()->words(3))));
            $entry->set('author_id', $this->getFakeMemberId());
            $entry->set('ip_address', $this->faker()->ipv4());
            $entry->set('entry_date', $this->faker()->dateTime->format('U'));
            $entry->set('edit_date', $this->faker()->dateTime());

            if (!empty($product_channel_map[$this->getChannelId()]['price'])) {
                $entry->setFieldValue($product_channel_map[$this->getChannelId()]['price'], $this->faker()->randomNumber(3));
            }

            if (!empty($product_channel_map[$this->getChannelId()]['shipping'])) {
                $entry->setFieldValue($product_channel_map[$this->getChannelId()]['shipping'], $this->faker()->randomNumber(2));
            }

            if (!empty($product_channel_map[$this->getChannelId()]['weight'])) {
                $entry->setFieldValue($product_channel_map[$this->getChannelId()]['weight'], $this->faker()->randomNumber(1, true));
            }

            if (!empty($product_channel_map[$this->getChannelId()]['inventory'])) {
                $entry->setFieldValue($product_channel_map[$this->getChannelId()]['inventory'], $this->faker()->randomNumber(3));
            }

            if (!empty($product_channel_map[$this->getChannelId()]['global_price'])) {
                $entry->setFieldValue($product_channel_map[$this->getChannelId()]['global_price'], '');
            }

            foreach ($entry->getFields()->allFields($this->getChannelId()) as $field) {
                $channel_field = $entry->getFields()->getField($field['field_name'], $this->getChannelId());
                if ($channel_field instanceof AbstractField && $channel_field instanceof SeedInterface) {
                    // we don't want to overwrite the hard sets we have above so only fakie the data that isn't set
                    if (!$entry->hasBeenSet($channel_field->getFieldName())) {
                        $entry->set($channel_field->getFieldName(), $channel_field->fakieData($this->faker(), $this));
                    }
                }
            }

            $entry->save();
            $this->pk = $entry->getEntryId();

            return $this;
        }
    }
}
