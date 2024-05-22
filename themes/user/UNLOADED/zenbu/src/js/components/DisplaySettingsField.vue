<template>
	<div class="">
		<fieldset class="col-group">
			<div class="setting-field w-16 last">
				<div class="col w-8 relate-wrap">
					<h4>Available fields</h4>
					<div class="scroll-wrap">
						<label class="choice block" v-for="(item, index) in fields" :key="item.field_id">
							<input type="checkbox" name="select[]" :value="item.field_id"
								   :checked="isInSelectedFieldIds(item.field_id)"
								   @click="updateSelectedFieldIdsOnStore(item.field_id)"> {{ item.field_label }} <i
								v-if="item.field_type">&mdash; [ {{item.field_name}} / {{item.field_type}}]</i>
						</label>
					</div>
				</div>
				<div class="col w-8 relate-wrap last">
					<h4>Visible fields</h4>
					<div class="scroll-wrap" v-sortable="sortableOptions">
						<label class="choice block chosen relate-manage" v-for="item in selectedFields"
							   :key="item.field_id">
							<span class="relate-reorder"></span>
							{{item.field_label}} <i v-if="item.field_type">&mdash; [ {{item.field_name}} /
							{{item.field_type}}]</i>
							<button type="button" class="field-settings" title="Settings"
									@click="toggleFieldSettings(item.field_id)" v-if="item.setting_fields">
								<i class="fa fa-lg fa-cogs"></i>
							</button>
							<div class="field-settings" v-show="isFieldSettingsOpen(item.field_id) && item.setting_fields">
								<div v-for="setting in item.setting_fields">

									<div v-if="setting.field_type === 'input'">
										<label v-text="setting.label"></label>
										<input type="text" :name="setting.name" placeholder=""
											   :placeholder="setting.placeholder" v-model="setting.value" @keyup="saveDisplaySettings">
									</div>

									<div v-if="setting.field_type === 'select'">
										<label v-text="setting.label"></label>
										<select :name="setting.name" v-model="setting.value" @change="saveDisplaySettings">
											<option v-for="label, value in setting.options" :value="value"
													:selected="value == setting.default">{{label}}
											</option>
										</select>
									</div>

									<div v-if="setting.field_type === 'checkbox'">
										<label>
											<input type="checkbox" :name="setting.name" v-model="setting.value" @click="saveDisplaySettings"> {{setting.label}}
										</label>
									</div>

								</div>
							</div>
						</label>
					</div>
				</div>
			</div>
		</fieldset>

		<pre v-for="(item, key) in selectedFields" style="display: none">
            {{key}} : {{item.field_id}}
        </pre>

	</div>
</template>

<script>

	module.exports = {
		name: "Display-Settings-Field",
		directives: {
			sortable: function(el, binding) {
				var sortable = new Sortable(el, binding.value || {});
			}
		},
		props: {
			fields: {},
		},
		data: function() {
			return {
				localSelectedFieldIds: [],
				openedFieldSettings: [],
				fieldSettings: {},
				limitDropdownOptions: this.$store.state.limit_dropdown_options,
			}
		},
		methods: {
			toggleFieldSettings: function(field_id) {
				if(_.includes(this.openedFieldSettings, field_id))
				{
					this.openedFieldSettings.splice(_.findIndex(this.openedFieldSettings, field_id), 1);
				}
				else
				{
					this.openedFieldSettings.push(field_id);
				}
			},
			isFieldSettingsOpen: function(field_id) {
				return _.includes(this.openedFieldSettings, field_id);
			},
			isInSelectedFieldIds: function(field_id) {
				return _.includes(this.selectedFieldIds, field_id);
			},
			/**
			 * Updates the selected fields (on store).
			 * @param e event   The onEnd sortable.js event
			 */
			updateOrder: function(e) {
				let field_id = this.selectedFieldIds[e.oldIndex];
				this.selectedFieldIds.splice(e.oldIndex, 1);
				this.selectedFieldIds.splice(e.newIndex, 0, field_id);

				let here = this;
				let fields = _.map(this.selectedFieldIds, function(id) {
					return _.find(here.selectedFields, function(f) {
						return f.field_id == id;
					});
				});

				// Save selected entries to store
				this.$store.dispatch('updateSelectedFields', fields);
				this.$store.dispatch('saveDisplaySettings', here.$parent.channel_id);
			},
			/**
			 * Updates the selected fields (on store) by adding or removing
			 * the specified field, which in turn is found from a field_id.
			 * @param field_id  int The field ID used to retrieve the field data array
			 */
			updateSelectedFieldIdsOnStore: function(field_id) {
				let here = this;
				this.$nextTick(function() {
					let field = _.find(here.fields, function(f) {
						return f.field_id == field_id;
					});

					// Save field to selected fields array on store
					here.$store.dispatch('updateSelectedFieldsWithField', field);
					here.$store.dispatch('saveDisplaySettings', here.$parent.channel_id);
				})
			},
			updateFieldSettings: function(field_id, setting_name) {
				if(_.isUndefined(this.fieldSettings[field_id]))
				{
					this.fieldSettings[field_id] = { setting: setting_name };
				}
			},
			saveDisplaySettings: _.throttle(function() {
				let here = this;
				this.$nextTick(function() {
					here.$store.dispatch('saveDisplaySettings', here.$parent.channel_id);
				});
			}, 1000),
		},
		computed: {
			selectedFieldIds: function() {
				return this.$store.getters.selected_field_ids;
			},
			selectedFields: function() {
				return this.$store.state.selected_fields;
			},
			filterSettings: function() {
				return this.$store.state.filter_settings;
			},
			sortableOptions: function() {
				let here = this;
				return {
					animation: 150,
					onEnd: function(e) {
						here.updateOrder(e);
					},
					ghostClass: 'sortable-ghost',
					handle: '.relate-reorder',
				}
			}
		},
		mounted: function() {

		}

	};
</script>

<style scoped>
	.relate-reorder {
		border-right: none;
	}

	div.setting-field {
		margin-top: 10px;
	}

	button.field-settings {
		background: none;
		float: right;
	}

	div.field-settings {
		clear: both;
	}

	div.field-settings label {
		margin-bottom: 0.5em;
		display: block;
	}

	fieldset.col-group .choice select {
		margin-left: 0;
	}

	.sortable-ghost {
		opacity: 0.5;
	}

	.scroll-wrap,
	.relate-wrap .scroll-wrap {
		max-height: 100%;
		height: 400px;
	}
</style>