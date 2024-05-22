import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex);

import { handleToken } from './../helpers.js';

export const store = new Vuex.Store({
	state: {
		csrf_token: handleToken,
		lang: languageStrings,
		channel_id: startingChannelId,
		rows: startingRows,
		limit: startingLimit,
		limit_dropdown_options: limitDropdownOptions,
		order_by: startingOrderBy,
		sort: startingSort,
		member_groups: memberGroups,
		entries: [],
		pagination_data: [],
		custom_fields: [],
		selected_fields: [],
		selected_entries: [],
		display_settings: [],
		saved_searches: [],
		options1: {},
		skip_run_search_trigger: false,
		data_save_state: null,
		searching: false,
		debug_mode: debugMode,
		search_error: null,
		filter_settings: {
			default_limit: startingLimit,
		},
	},
	//showing things, not mutating state
	getters: {
		selected_field_ids: function(state) {
			return _.map(state.selected_fields, function(f) {
				return f.field_id;
			});
		},
		result_columns: function(state) {
			return _.map(state.display_settings, function(ds) {
				return ds.fieldType == 'field' ? ds.fieldId : ds.fieldType;
			});
		},
		basic_and_custom_field_options: function(state) {
			var out = {};
			out[state.lang.basic_fields] = [
				{id: 'title', text: state.lang.title},
				{id: 'entry_id', text: state.lang.entry_id},
				{id: 'url_title', text: state.lang.url_title},
				{id: 'status_id', text: state.lang.status},
				{id: 'author_id', text: state.lang.author},
				{id: 'category_id', text: state.lang.category},
				{id: 'sticky', text: state.lang.sticky},
			];
			out[state.lang.dates] = [
				{id: 'entry_date', text: state.lang.entry_date},
				{id: 'edit_date', text: state.lang.edit_date},
				{id: 'expiration_date', text: state.lang.expiration_date},
			];

			if(_.size(state.custom_fields) > 0)
			{
				var custom_field_options = {
					'Custom Fields':
						_.map(state.custom_fields, function(f) {
							return {id: f.field_id, text: f.field_label };
						}),
				};
			}
			else
			{
				var custom_field_options = {};
			}

			if(_.size(custom_field_options) > 0)
			{
				out = _.merge(out, custom_field_options);
			}

			return out;
		},
	},
	//mutating the state
	//mutations are always synchronous
	mutations: {
		updateCSRFToken: function(state) {
			state.csrf_token = handleToken();
		},
		//showing passed with payload, represented as num
		updateChannelId: function(state, channel_id) {
			Vue.set(state, 'channel_id', channel_id);
		},
		updateRows: function(state, rows) {
			// @ref of where I got the idea of Vue.set: https://stackoverflow.com/questions/50767191/vuex-update-an-entire-array
			Vue.set(state, 'rows', rows);
			// state.rows = rows;
		},
		updateRow: function(state, data) {
			let map = _.map(state.rows, function(row, index) {
				if(index == data.index && !_.isUndefined(row[data.filterPos]))
				{
					row[data.filterPos] = data.value;
				}

				return row;
			});
			Vue.set(state, 'rows', map);
			// state.rows[data.index][data.filterPos] = data.value;
		},
		updateEntries: function(state, entries) {
			state.entries = entries;
		},
		updatePaginationData: function(state, pagination_data) {
			state.pagination_data = pagination_data;
		},
		updateLimit: function(state, limit) {
			Vue.set(state, 'limit', limit);
		},
		updateOrderBy: function(state, order_by) {
			state.order_by = order_by;
		},
		updateSort: function(state, sort) {
			state.sort = sort;
		},
		updateCustomFields: function(state, fields) {
			state.custom_fields = fields;
		},
		updateSelectedFields: function(state, selected_fields) {
			state.selected_fields = selected_fields;
		},
		updateSelectedEntries: function(state, selected_entries) {
			state.selected_entries = selected_entries;
		},
		updateSelectedFieldsWithField: function(state, field) {
			let fieldIds = _.map(state.selected_fields, function(f) {
				return f.field_id
			});
			if(_.includes(fieldIds, field.field_id))
			{
				// Remove field if present
				state.selected_fields.splice(_.findIndex(state.selected_fields, field), 1);
			}
			else
			{
				// Add field to selected fields array if not found
				state.selected_fields.push(field);
			}
		},
		updateFilterSettings: function(state, filter_settings) {
			state.filter_settings = filter_settings;
		},
		updateDisplaySettings: function(state, display_settings) {
			state.display_settings = display_settings;
		},
		updateSavedSearches: function(state, saved_searches) {
			state.saved_searches = saved_searches;
		},
		updateOptions1: function(state, options1) {
			var out = {};
			out[state.lang.basic_fields] = [
				{id: 'title', text: state.lang.title},
				{id: 'entry_id', text: state.lang.entry_id},
				{id: 'url_title', text: state.lang.url_title},
				{id: 'status_id', text: state.lang.status},
				{id: 'author_id', text: state.lang.author},
				{id: 'category_id', text: state.lang.category},
				{id: 'sticky', text: state.lang.sticky},
			];
			out[state.lang.dates] = [
				{id: 'entry_date', text: state.lang.entry_date},
				{id: 'edit_date', text: state.lang.edit_date},
				{id: 'expiration_date', text: state.lang.expiration_date},
			];

			if(options1)
			{
				state.options1 = _.merge(out, options1);
			}
			else
			{


				state.options1 = out;
			}
		},
		updateSkipRunSearchTrigger: function(state, value) {
			state.skip_run_search_trigger = value;
		},
		updateDataSaveState: function(state, value) {
			state.data_save_state = value;
		},
		updateSearching: function(state, value) {
			state.searching = value;
		},
		updateSearchError: function(state, value) {
			state.search_error = value;
		},
	},
	//commits the mutation, it's asynchronous
	actions: {
		updateCSRFToken: function(context) {
			context.commit('updateCSRFToken');
		},
		updateChannelId: function(context, channel_id) {
			context.commit('updateChannelId', channel_id);
		},
		updateRows: function(context, rows) {
			context.commit('updateRows', rows);
		},
		updateLimit: function(context, limit) {
			context.commit('updateLimit', limit);
		},
		updateOrderBy: function(context, order_by) {
			context.commit('updateOrderBy', order_by);
		},
		updateSort: function(context, sort) {
			context.commit('updateSort', sort);
		},
		updateSelectedFields: function(context, selected_fields) {
			context.commit('updateSelectedFields', selected_fields);
		},
		updateSelectedFieldsWithField: function(context, field) {
			context.commit('updateSelectedFieldsWithField', field);
		},
		updateFilterSettings: function(context, filter_settings) {
			context.commit('updateFilterSettings', filter_settings);
		},
		getCustomFields: function(context) {
			let channel_id = this.state.channel_id;
			axios.post(buildUrl('/cp/addons/settings/zenbu/fetch_fields'), qs.stringify({channel_id: channel_id, csrf_token: this.state.csrf_token}))
				.then(function(response) {
					if(channel_id)
					{
						context.commit('updateCustomFields', response.data);
					}
					else
					{
						context.commit('updateCustomFields', {});
					}
				})
				.catch(function(error) {
					console.error(error);
				})
		},
		runSearch: _.throttle(function(context, page) {

			context.commit('updateSearching', true);
			context.commit('updateCSRFToken');

			// Cancel previous calls
			let cancelMessage = 'store@runSearch: Attempt cancellation of older Ajax calls.';
			CancelTokenSource.cancel(cancelMessage);

			// Make a new token
			CancelTokenSource = CancelToken.source();

			var postData = qs.stringify({
				channel_id: this.state.channel_id,
				rows: this.state.rows,
				limit: this.state.limit,
				order_by: this.state.order_by,
				sort: this.state.sort,
				csrf_token: this.state.csrf_token,
				page: page ? page : 1,
			});
			axios.post(buildUrl('/cp/addons/settings/zenbu/search'), postData, {
				cancelToken: CancelTokenSource.token,
			})
				.then(function(response) {
					if(! response.data.data)
					{
						context.commit('updateSearchError', response);
					}
					else
					{
						context.commit('updateSearchError', null);
					}

					context.commit('updateEntries', response.data.data);
					context.commit('updatePaginationData', response.data.pagination_data);
					context.commit('updateDisplaySettings', response.data.display_settings);
					context.commit('updateSkipRunSearchTrigger', false);
					context.commit('updateSearching', false);
				})
				.catch(function(error) {
					let response = error.response;

					if(! _.isUndefined(error.message) && error.message == cancelMessage)
					{
						context.commit('updateSearching', true);
						console.warn(error.message);
					}
					else
					{
						context.commit('updateSearching', false);
						console.error(error);
					}

					context.commit('updateSearchError', response);
					context.commit('updateEntries', []);
					context.commit('updatePaginationData', []);
					context.commit('updateDisplaySettings', []);
					context.commit('updateSkipRunSearchTrigger', false);
				})
		}, 700),
		saveDisplaySettings: function(context, channel_id) {

			context.commit('updateDataSaveState', 'saving');
			context.commit('updateCSRFToken');

			var postData = qs.stringify({
				data: this.state.selected_fields,
				filter_settings: this.state.filter_settings,
				channel_id: channel_id,
				csrf_token: this.state.csrf_token,
			});

			axios.post(buildUrl('/cp/addons/settings/zenbu/save_display_settings_for_user'), postData)
				.then(function(response) {
					context.commit('updateDataSaveState', 'saved');
				})
				.catch(function(error) {
					console.error(error);
					context.commit('updateDataSaveState', 'error');
				})
		},
		saveDisplaySettingsForGroup: function(context, data) {

			context.commit('updateDataSaveState', 'saving');
			context.commit('updateCSRFToken');

			var postData = qs.stringify({
				data: this.state.selected_fields,
				filter_settings: this.state.filter_settings,
				channel_id: data.channel_id,
				member_group_id: data.member_group_id,
				csrf_token: this.state.csrf_token,
			});

			axios.post(buildUrl('/cp/addons/settings/zenbu/save_display_settings_for_group'), postData)
				.then(function(response) {
					context.commit('updateDataSaveState', 'saved');
				})
				.catch(function(error) {
					console.error(error);
					context.commit('updateDataSaveState', 'error');
				})
		},
		updateSavedSearches: function(context, saved_searches) {
			context.commit('updateSavedSearches', saved_searches);
		},
	}
});