<?php

namespace CartThrob\Traits;

use ExpressionEngine\Service\Validation\Result as ValidateResult;
use ExpressionEngine\Service\Validation\Validator;

trait ValidateTrait
{
    /**
     * The ExpressionEngine Validation Rules array
     * @var array
     */
    protected array $rules = [];

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @var array
     */
    protected array $set_data = [];

    /**
     * @return array
     */
    public function getValidationData(): array
    {
        return array_merge($this->toArray(), $this->set_data);
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return $this->rules;
    }

    /**
     * Validates the submitted data
     * @param array $post_data
     * @return ValidateResult
     */
    public function validate(array $post_data = []): ValidateResult
    {
        // return $this->getValidator()->validate($post_data);
        $this->data = $post_data;

        return $this->getValidator()->validate($post_data);
    }

    /**
     * @return Validator
     */
    protected function getValidator(): Validator
    {
        $validator = ee('Validation')->make($this->rules);

        return $validator;
    }
}
