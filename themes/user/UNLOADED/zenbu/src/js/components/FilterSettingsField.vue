<template>
	<div class="filter-settings">

			<h2 v-text="lang.filter_settings"></h2>

			<p class="setting-txt">
				<em v-text="lang.filter_settings_info"></em>
			</p>

		<fieldset class="settings col-group">
			<div class="col-group">
				<div class="setting-txt col w-12">
					<h4 v-text="lang.starting_limit"></h4>
					<em v-text="lang.starting_limit_info"></em>
				</div>
				<div class="setting-field col w-4">
					<select2 :options="limitDropdownOptionsMapped" v-model="filterSettings.starting_limit" @change="saveFilterSettings" :class="{ starting_limit: true }"></select2>
				</div>
			</div>
		</fieldset>

		<fieldset class="settings col-group">
			<div class="col-group">
				<div class="setting-txt col w-12">
					<h4 v-text="lang.starting_order_by"></h4>
					<em v-text="lang.starting_order_by_info"></em>
				</div>
				<div class="setting-field col w-4">
					<select2 :options="orderByDropdownOptions" v-model="filterSettings.starting_order_by" @change="saveFilterSettings" :class="{ starting_order_by: true }"  :has-optgroups="true"></select2>
				</div>
			</div>
		</fieldset>

		<fieldset class="settings col-group">
			<div class="setting-txt col w-12">
				<h4 v-text="lang.starting_sort"></h4>
				<em v-text="lang.starting_sort_info"></em>
			</div>
			<div class="setting-field col w-4">
				<select2 :options="sortDropdownOptions" v-model="filterSettings.starting_sort" @change="saveFilterSettings" :class="{ starting_sort: true }"></select2>
			</div>
		</fieldset>

	</div>
</template>

<script>

	module.exports = {
		name: "Filter-Settings-Field",
		props: {
		},
		data: function() {
			return {
				lang: this.$store.state.lang,
				limitDropdownOptions: this.$store.state.limit_dropdown_options,
			}
		},
		methods: {
			saveFilterSettings: _.throttle(function() {
				let here = this;
				this.$nextTick(function() {
					here.$store.dispatch('saveDisplaySettings', here.$parent.channel_id);
				});
			}, 1000),
		},
		computed: {
			filterSettings: function() {
				return this.$store.state.filter_settings;
			},
			limitDropdownOptionsMapped: function() {
				return _.map(this.limitDropdownOptions, function(item) { return {id: item, text: item}});
			},
			sortDropdownOptions: function() {
				return [
					{id: 'desc', text: this.$store.state.lang.desc},
					{id: 'asc', text: this.$store.state.lang.asc}
				];
			},
			orderByDropdownOptions: function() {
				return this.$store.getters.basic_and_custom_field_options;
			},
		},
		mounted: function() {

		}

	};
</script>

<style scoped>

	.settings.col-group {
		padding-bottom: 10px;
	}
</style>