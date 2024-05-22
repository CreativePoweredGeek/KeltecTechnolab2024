<template>
  <select class="form-control" :multiple="multiple" @focus="clearSelectError($event)">
  		<slot></slot>
    	<option v-for="(item, key, index) in options" :value="item.id" v-if="! hasOptgroups">{{item.text}}</option>

		<optgroup v-for="(opts, groupLabel, groupIndex) in options" :label="groupLabel" v-if="hasOptgroups">
    		<option v-for="(item, key, index) in opts" :value="item.id">{{item.text}}</option>
		</optgroup>
  </select>
</template>

<script>
	// Loading our own instance of jQuery for Select2
	// since it complains if jQuery isn't loaded from our
	// own scripts. And we can't load from our own scripts
	// because that can mess up the native EE jQuery instance.
	// So custom load it is.
	try {
		window.jQueryForSelect2 = require('jquery');
	} catch (e) {
		console.error(e);
	}
	module.exports = {
        name: "Select2",
		props: {
			value: null,
			// object/array defaults should be returned from a
    		// factory function
			options: {
				type: null,
				default: function() {
					return [];
				}
			},
			allowClear: {
				type: Boolean,
				default: false,
			},
			multiple: {
				type: Boolean,
				default: false,
			},
			select2TriggerThreshold: {
				// type: Number,
				default: 0
			},
			modalFix: {
				type: Boolean,
				default: false,
			},
			placeholder: {
				type: String,
				default: '-- Select --',
            },
			hasOptgroups: {
				type: Boolean,
				default: false,
			},
			minimumResultsForSearch: {
				default: Infinity,
			},
        },
        data: function() {
			return {
	  			// success: this.success
		  	}
        },
		mounted: function () {
			var vm = this;

			jQueryForSelect2(this.$el).val(this.value);

			// Initialize select2
			let options = {
				minimumResultsForSearch: this.minimumResultsForSearch,
				placeholder: this.placeholder,
				data: function() {
					return vm.options;
				},
				theme: "bootstrap",
				allowClear: this.allowClear,
				width: '100%',
				escapeMarkup: function(m) {
					// Do not escape HTML in the select options text
					return m;
				},
			};

			if(this.modalFix == true)
			{
				// Fix to select2 not working in Bootstrap modals:
				// https://github.com/select2/select2/issues/1645#issuecomment-281291488
				options.dropdownParent = jQueryForSelect2(this.$el).closest('div.modal');
			}

			jQueryForSelect2(this.$el)
				.select2(options)
				.on('change', function(e) {
					vm.$emit('input', jQueryForSelect2(this).val());
					vm.$emit('change');
				});

			jQueryForSelect2(this.$el)
				.on('select2:reload', function(e) {
					vm.$nextTick(function() {
						if(jQueryForSelect2(vm.$el).data('select2'))
						{
							jQueryForSelect2(vm.$el).select2('destroy').select2(options);
						}
					});
				})
		},
		computed: {
        	totalOptions: function() {
        		return 	_.size(this.options);
			},
		},
		watch: {
			value: function (value) {
				// Update value
				jQueryForSelect2(this.$el).val(value);
			},
			options: function (options) {

					let vm = this;

					var select2options = {
						minimumResultsForSearch: this.minimumResultsForSearch,
						placeholder: this.placeholder,
						data: function () {
                            return options
                            },
						theme: "bootstrap",
						allowClear: this.allowClear,
						width: '100%',
						escapeMarkup: function(m) {
							// Do not escape HTML in the select options text
							return m;
						},
					};

					// Update select2 options.
                    // Using nextTick or else data
                    // might contain previous option content.
                    this.$nextTick(function() {
                    	if(jQueryForSelect2(vm.$el).data('select2'))
						{
	                        jQueryForSelect2(vm.$el).select2('destroy').select2(select2options);
						}
                    });
			},
		},
		destroyed: function () {
			if(jQueryForSelect2(this.$el).data('select2'))
			{
				jQueryForSelect2(this.$el).select2('destroy');
			}
		},
		methods: {
		  /**
	       * Clear error on focus
	       */
	      clearSelectError: function(event) {
	        // if(! _.isUndefined(event.target.name) && ! _.isUndefined(vm.errors) && ! _.isUndefined(vm.errors[event.target.name]))
	        // {
	        //   vm.errors[event.target.name] = false;
	        // }
	      },
		}
	}
</script>