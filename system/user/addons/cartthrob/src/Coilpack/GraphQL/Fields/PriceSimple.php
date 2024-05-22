<?php

namespace CartThrob\Coilpack\GraphQL\Fields;

use Expressionengine\Coilpack\FieldtypeOutput;
use Expressionengine\Coilpack\Fieldtypes\Generic;
use Expressionengine\Coilpack\Models\FieldContent;

class PriceSimple extends Generic
{
    /**
     * @param FieldContent $content
     * @param array $parameters
     * @return FieldtypeOutput
     */
    public function apply(FieldContent $content, array $parameters = [])
    {
        $handler = clone $this->getHandler();

        // Set entry data on handler
        $handler->_init(array_merge($this->settings ?? [], [
            'content_id' => $content->entry_id,
        ]));
        $handler->row = $content->entry->toArray();

        $data = $content->getAttribute('data');

        // Run pre_process if it exists
        if (method_exists($handler, 'pre_process')) {
            $data = $handler->pre_process($data);
        }

        $output = \Expressionengine\Coilpack\Facades\Coilpack::isolateTemplateLibrary(function ($template) use ($handler, $data, $parameters) {
            $output = $handler->replace_tag($data, $parameters);
            // If the Fieldtype stored data for us in the template library that is preferable to the generated output
            return $template->get_data() ?: $output;
        });

        return FieldtypeOutput::for($this)->value($output);
    }
}
