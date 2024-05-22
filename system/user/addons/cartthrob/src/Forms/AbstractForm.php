<?php

namespace CartThrob\Forms;

use CartThrob\Exceptions\Forms\AbstractFormExceptions;
use CartThrob\Services\SettingsService;
use ExpressionEngine\Service\Validation\Result as ValidateResult;
use ExpressionEngine\Service\Validation\ValidationAware;
use ExpressionEngine\Service\Validation\Validator;

abstract class AbstractForm implements ValidationAware
{
    /**
     * @var int
     */
    protected $channel_id = 0;

    /**
     * @var SettingsService
     */
    protected $settings = null;

    /**
     * @var array
     */
    protected $field_options = [];

    /**
     * @var array
     */
    protected $channel_statuses = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var bool
     */
    protected $base_url = false;

    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @var \string[][]
     */
    protected $options = [
        'yes_no' => [
            '1' => 'Yes',
            '0' => 'No',
        ],
    ];

    /**
     * @var int
     */
    protected int $channel_field_group_id = 0;

    /**
     * AbstractForm constructor.
     */
    public function __construct()
    {
        ee()->load->library('locales');
    }

    /**
     * @return array|string[]
     */
    protected function getStateOptions(): array
    {
        $states = ee()->locales->states();

        return array_merge(['' => '---'], $states);
    }

    /**
     * @return array|string[]
     */
    protected function getCountryOptions($empty_first = true): array
    {
        $countries = ee()->locales->all_countries();
        if ($empty_first) {
            return array_merge(['' => '---'], $countries);
        }

        return $countries;
    }

    /**
     * @return array
     */
    protected function getCountryStateOptions(): array
    {
        $return = ['global' => lang('global'), '' => '---'];
        $countries = ee()->locales->all_countries();
        $states = ee()->locales->states();

        return array_merge($return, $states, ['0' => '---'], $countries);
    }

    /**
     * @return array
     */
    protected function roleOptions(): array
    {
        $groups = [];
        $query = ee('Model')
            ->get('Role')
            ->filter('role_id', '>=', '5')
            ->order('name', 'asc')
            ->all();

        foreach ($query as $row) {
            $groups[$row->role_id] = $row->name;
        }

        return $groups;
    }

    /**
     * @return string[]
     */
    protected function getChannelFieldOptions(): array
    {
        if ($this->channel_id) {
            $this->setFieldOptions();

            return $this->field_options;
        }

        return [];
    }

    /**
     * @param $channel_id
     * @return $this
     */
    public function setChannelId($channel_id)
    {
        $this->channel_id = $channel_id;

        return $this;
    }

    /**
     * @return int
     */
    public function getChannelId()
    {
        return $this->channel_id;
    }

    /**
     * @return mixed
     */
    public function getChannelTitle()
    {
        $channel = $this->getChannel();
        if ($channel) {
            return $channel->channel_title;
        }
    }

    /**
     * @return mixed
     * @throws AbstractFormExceptions
     */
    protected function getChannel()
    {
        if (is_null($this->getChannelId())) {
            throw new AbstractFormExceptions("There isn't a channel_id value set on this Form");
        }

        return ee('Model')
            ->get('Channel')
            ->filter('channel_id', $this->getChannelId())
            ->first();
    }

    /**
     * @param SettingsService $settings
     * @return $this
     */
    public function setSettings(SettingsService $settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @return SettingsService|null
     * @throws AbstractFormExceptions
     */
    protected function settings()
    {
        if (is_null($this->settings)) {
            throw new AbstractFormExceptions("The Settings Service isn't available for this form!");
        }

        return $this->settings;
    }

    /**
     * @return array
     */
    protected function getChannelStatuses(bool $first_empty = false): array
    {
        if (!$this->getChannelId()) {
            return [];
        }

        if (!$this->channel_statuses) {
            $channel = ee('Model')->get('Channel')
                ->fields('Channel.channel_id', 'Statuses.*')
                ->with('Statuses')
                ->filter('channel_id', $this->getChannelId())
                ->order('Statuses.status_order', 'asc')
                ->all()
                ->first();

            if ($first_empty) {
                $this->channel_statuses[''] = '--';
            }

            $this->channel_statuses['ANY'] = 'ANY';
            $statuses = $channel->Statuses->toArray();
            if ($statuses) {
                foreach ($statuses as $status) {
                    $this->channel_statuses[$status['status']] = $status['status'];
                }
            }
        }

        return $this->channel_statuses;
    }

    /**
     * @param int $channel_id
     * @return int
     */
    protected function getChannelFieldGroupId(int $channel_id): int
    {
        if (!$this->channel_field_group_id) {
            $groups = ee()->db->select('group_id')->from('channels_channel_field_groups')
                ->where(['channel_id' => $channel_id])->limit(1)->get();
            if ($groups->num_rows() == 1) {
                $this->channel_field_group_id = $groups->row('group_id');
            }
        }

        return $this->channel_field_group_id;
    }

    /**
     * Should return an EE Shared form array
     * @return mixed
     */
    abstract public function generate(): array;

    /**
     * The form data to populate with
     * @param array $data
     * @return $this
     */
    public function setData(array $data): AbstractForm
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setBaseUrl(string $url): AbstractForm
    {
        $this->base_url = $url;

        return $this;
    }

    /**
     * @return void
     * @throws AbstractFormExceptions
     */
    protected function setFieldOptions()
    {
        if (empty($this->field_options)) {
            $this->field_options = ['' => '---'];

            $channel = $this->getChannel();

            foreach ($channel->FieldGroups as $group) {
                foreach ($group->ChannelFields->toArray() as $field) {
                    $this->field_options[$field['field_id']] = $field['field_label'];
                }
            }

            foreach ($channel->CustomFields->toArray() as $field) {
                $this->field_options[$field['field_id']] = $field['field_label'];
            }
        }
    }

    /**
     * Returns a piece of data based on $key
     * @param string $key
     * @param string $default
     * @return mixed|string
     */
    public function get(string $key = '', $default = '')
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
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

        return $this->getValidator()->validate($this);
    }

    /**
     * @return Validator
     */
    protected function getValidator(): Validator
    {
        $validator = ee('Validation')->make($this->rules);

        return $validator;
    }

    /**
     * @return array
     */
    public function getValidationData(): array
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return $this->rules;
    }

    /**
     * @param string $name
     * @param $value
     * @param $params
     * @param $object
     * @return bool|string
     */
    public function validateMemberExists(string $name, $value, $params, $object)
    {
        $member = ee('Model')
            ->get('Member')
            ->filter('member_id', $value)
            ->first();

        if ($member instanceof \ExpressionEngine\Model\Member\Member) {
            return true;
        }

        return 'ct.error.invalid_member_id';
    }

    /**
     * @param string $name
     * @param $value
     * @param $params
     * @param $object
     * @return bool|string
     */
    public function validateMemberIsSuperAdmin(string $name, $value, $params, $object)
    {
        $member = ee('Model')
            ->get('Member')
            ->filter('member_id', $value)
            ->first();

        if ($member instanceof \ExpressionEngine\Model\Member\Member) {
            if ($member->isSuperAdmin()) {
                return true;
            }
        }

        return 'ct.error.invalid_super_admin_id';
    }

    /**
     * @param string $name
     * @param $value
     * @param $params
     * @param $object
     * @return string|true
     */
    public function validateOrdersSaved(string $name, $value, $params, $object)
    {
        if (bool_string(ee('cartthrob:SettingsService')->get('cartthrob', 'save_orders'))) {
            return true;
        }

        return 'ct.error.order_save_must_be_enabled';
    }
}
