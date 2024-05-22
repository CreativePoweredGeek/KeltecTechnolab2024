<?php

namespace CartThrob;

use CartThrob\Math\Number;

trait CleansSubscriptionData
{
    /**
     * @param array $item_subscription_options
     * @param bool $update
     * @return array|bool
     */
    private function clean_sub_data($item_subscription_options = [], $update = false)
    {
        // if item and SUB OR subscription (to account for select boxes)
        // OR if item AND corresponding product has subscription fieldtype and is enabled
        if (!empty($item_subscription_options['subscription_enabled']) || $this->request->decode('SUB') || $this->request->has('subscription')) {
            // these are all of the subscription options
            // @TODO make a decision about these? do we need allow_user_subscription_trial_price or allow_user="trial_price|start_date"
            $subscription = [];

            // iterating through those options. if they're in post, we'll add them to the "subscription_options" meta
            foreach (ee()->subscription_model->option_keys() as $encoded_key => $key) {
                $option = null;

                if ($update && array_key_exists($key, $item_subscription_options)) {
                    $option = $item_subscription_options[$key];
                }

                // a couple of these things can be plain text
                if ($this->request->has('subscription_' . $key)) {
                    switch ($key) {
                        case 'name':
                        case 'description':
                            $option = $this->request->input('subscription_' . $key);
                            break;
                    }
                }

                if (!$option && $this->request->has($encoded_key)) {
                    $option = $this->request->decode($encoded_key);
                } elseif (!$option && $this->request->has($key)) {
                    $option = $this->request->decode($key);
                } elseif (!$option && isset($item_subscription_options['subscription_' . $key])) {
                    switch ($key) {
                        case 'end_date':
                        case 'start_date':
                            if (!$item_subscription_options['subscription_' . $key]) {
                                $option = '';
                            } elseif (!is_numeric($item_subscription_options['subscription_' . $key])) {
                                $option = strtotime($item_subscription_options['subscription_' . $key]);
                            } else {
                                $option = $item_subscription_options['subscription_' . $key];
                            }
                            break;
                        default:
                            if (!$update) {
                                $option = $item_subscription_options['subscription_' . $key];
                            }
                    }
                }

                if (!is_null($option)) {
                    if (in_array($encoded_key, ee()->subscription_model->encoded_bools())) {
                        if (!$update) {
                            $option = bool_string($option);
                        } elseif ($key != 'allow_modification') {
                            $option = bool_string($option);
                        }
                    }

                    if (strncmp($key, 'subscription_', 13) === 0) {
                        $key = substr($key, 13);
                    }

                    $subscription[$key] = $option;
                }
            }

            if (empty($subscription['price']) && bool_string($this->request->decode('AUP', false)) && !is_null($this->request->input('price'))) {
                $subscription['price'] = abs(Number::sanitize($this->request->input('price')));
            }
        }

        if (isset($subscription)) {
            return $subscription;
        }

        return false;
    }
}
