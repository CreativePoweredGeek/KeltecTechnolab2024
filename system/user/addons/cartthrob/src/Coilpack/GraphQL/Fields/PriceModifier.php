<?php

namespace CartThrob\Coilpack\GraphQL\Fields;

use Expressionengine\Coilpack\FieldtypeOutput;
use Expressionengine\Coilpack\Fieldtypes\Generic;
use Expressionengine\Coilpack\Models\FieldContent;

class PriceModifier extends Generic
{
    public function apply(FieldContent $content, $parameters = [])
    {
        $handler = clone $this->getHandler();

        // Set entry data on handler
        $handler->_init(array_merge($this->settings ?? [], [
            'content_id' => $content->entry_id,
        ]));
        $handler->row = $content->entry->toArray();

        $data = $content->getAttribute('data');

        // Run pre_process if it exists
        $output = '';
        if (method_exists($handler, 'pre_process')) {
            $data = $handler->pre_process($data);
            $output = json_encode($data);
        }

        return FieldtypeOutput::for($this)->value($output);
    }
}
