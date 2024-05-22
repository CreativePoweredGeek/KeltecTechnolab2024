// https://github.com/JeffreyWay/laravel-mix/issues/436
import 'babel-polyfill';

window._ = require('lodash');

/**
 * jQuery loading
 */

// try {
//     window.$ = window.jQuery = require('jquery');
// } catch (e) { }

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Axios cancelletion.
 * @see https://github.com/axios/axios#cancellation
 */
window.CancelToken = axios.CancelToken;
window.CancelTokenSource = CancelToken.source();

window.qs = require('qs');

/**
 * Select2
 */
window.select2 = require('select2');

/**
 * Sortable
 */
window.Sortable = require('sortablejs');
Vue.directive('sortable', {
    inserted: function (el, binding) {
        var sortable = new Sortable(el, binding.value || {});
    }
});

import * as helpers from './helpers.js';

window.buildUrl 	= helpers.buildUrl;
window.handleToken 	= helpers.handleToken;

/**
 * Vue & Vuex
 *
 * Vue is a modern JavaScript library for building interactive web interfaces
 * using reactive data binding and reusable components. Vue's API is clean
 * and simple, leaving you to focus on building your next great project.
 */
import Vue from 'vue';
import Vuex from 'vuex';
Vue.use(Vuex);
import { mapState } from 'vuex';
import { mapGetters } from 'vuex';
import { store } from './store/store.js';

// Making the store global so that addons loaded from Zenbu (eg. Hokoku) can access it.
window.zenbuStore = store;

Vue.component('select2', require('./components/Select2.vue'));
Vue.component('flatpickr', require('./components/Flatpickr.vue'));
Vue.component('filter-row', require('./components/FilterRow.vue'));
Vue.component('result-table', require('./components/ResultTable.vue'));
Vue.component('pagination', require('./components/Pagination.vue'));
Vue.component('filter-settings-field', require('./components/FilterSettingsField.vue'));
Vue.component('display-settings-field', require('./components/DisplaySettingsField.vue'));
Vue.component('display-settings-copy-to-group', require('./components/DisplaySettingsCopyToGroup.vue'));
Vue.component('saved-searches-manager', require('./components/SavedSearchesManager.vue'));
Vue.component('permissions', require('./components/Permissions.vue'));
Vue.component('modal-save-status', require('./components/ModalSaveStatus.vue'));


/**
 * Search Filters
 * @type {CombinedVueInstance<V, {lang, csrf_token: *, channel_id: string, rows: string[][], limit: number}, Object, Object, Record<never, any>>}
 */
var vmFilters = new Vue({
    name: 'Search Form',
    el: '.zenbu-filters',
    store: store,
    delimiters: ['@{', '}'],
    data: {
    },
    mixins: [],
    computed: {
        ...mapState([
            'entries',
			'searching',
			'debug_mode',
        ]),
		// Using set/get for store states so that v-model in components
		// can be used to modify stored states.
		// @see Vuex docs: https://vuex.vuejs.org/guide/forms.html
		channel_id: {
        	get: function() {
				return this.$store.state.channel_id;
			},
			set: function(value) {
				value = value == 'all' ? '' : value;
				this.$emit('channelChanged', value);
				this.$store.commit('updateChannelId', value);
				this.$store.dispatch('getCustomFields');
				this.$store.dispatch('runSearch');
			}
		},
		rows: {
			get: function() {
				return this.$store.state.rows;
			},
			set: function(value) {
				this.$store.dispatch('updateRows', value);
			}
		},
		order_by: {
			get: function() {
				return this.$store.state.order_by;
			},
			set: function(value) {
				this.$store.commit('updateOrderBy', value);
			}
		},
		sort: {
			get: function() {
				return this.$store.state.sort;
			},
			set: function(value) {
				this.$store.commit('updateSort', value);
			}
		},
		limit: {
			get: function() {
				return this.$store.state.limit;
			},
			set: function(value) {
				this.$store.commit('updateLimit', value);
				this.$store.dispatch('runSearch');
			}
		},
		options1: function() {
        	return this.$store.getters.basic_and_custom_field_options;
		},
    },
    methods: {
        buildDropdownArray: function(langArray) {
            var here = this;
            return  _.map(langArray, function(langKey) {
                return { id: langKey, text: here.$store.state.lang[langKey] };
            });
        },
		getCustomFields: function() {
			let here = this;
			let channel_id = here.$store.state.channel_id;
			axios.post(buildUrl('/cp/addons/settings/zenbu/fetch_fields'), qs.stringify({channel_id: channel_id, csrf_token: here.$store.state.csrf_token}))
				.then(function(response) {
					if(channel_id)
					{
						here.customFields = response.data;
					}
					else
					{
						here.customFields = {};
					}
				})
				.catch(function(error) {
					console.error(error);
				})
		}
    },
    watch: {

    },
    mounted: function() {
        this.$store.commit('updateChannelId', this.channel_id);
        this.$store.commit('updateLimit', this.limit);
		this.$emit('channelChanged', this.channel_id);
		this.$store.dispatch('getCustomFields');
        this.$store.dispatch('runSearch', startingPage);
    },
});

/**
 * Results
 * @type {CombinedVueInstance<V, {}, Object, Object, Record<never, any>>}
 */
var vmResults = new Vue({
    el: '.resultArea',
    name: 'Results',
    delimiters: ['@{', '}'],
    store: store,
    data: {
    	showBulkMenuEntryEdit: false,
    	showBulkMenuCategoryEdit: false,
    },
    computed: {
        ...mapState([
            'lang',
            'entries',
            'pagination_data',
			'selected_entries'
        ]),
        ...mapGetters([
            'result_columns'
        ]),
    },
    methods: {
		/**
		 * Manually triggering a modal open
		 * @param rel
		 */
		triggerAppModal: function(rel, val) {
			$('.app-modal[rel=modal-'+ rel + ']').trigger("modal:open");
			if(_.isUndefined(val))
			{
				val = rel;
			}
			$('select[name=bulk_action]').val(val).next('.btn').click();

		},
		triggerBulkEditModal: function() {
			let val = $('select[name=bulk_action]').val();
			let rel = $('select[name=bulk_action]').find('option[value=' + val + ']').eq(0).attr('rel');
			if(rel == 'modal-bulk-edit')
			{
				$('.app-modal[rel=' + rel + ']').trigger("modal:open");
			}
		},
		toggleShowBulkMenu: function(toggleName) {
			return this[toggleName] = ! this[toggleName];
		}
    },
    mounted: function() {
    }
});

/**
 * Display Settings
 * @type {CombinedVueInstance<V, {channel_id: null, fields: Array}, Object, Object, Record<never, any>>}
 */
var vmDisplaySettings = new Vue({
    el: '.modal-displaySettings',
    name: 'Display Settings',
    delimiters: ['@{', '}'],
    store: store,
    data: {
        fields: [],
    },
    computed: {
        ...mapState([
            'lang',
            'selected_fields',
        ]),
        ...mapGetters([
            'selected_field_ids'
        ]),
		channel_id: {
			get: function() {
				return this.$store.state.channel_id;
			},
			set: function(value) {
				value = value == 'all' ? '' : value;
				this.$store.commit('updateChannelId', value);
				this.$store.dispatch('getCustomFields');
			},
		},
    },
    methods: {
        getSettings: function () {
			this.$store.dispatch('updateCSRFToken');

            var here = this;

            axios.post(buildUrl('/cp/addons/settings/zenbu/fetch_display_settings'), qs.stringify({
                channel_id: this.$store.state.channel_id,
                csrf_token: this.$store.state.csrf_token
            }))
                .then(function (response) {
                    here.fields = response.data.fields;
                    here.$store.dispatch('updateSelectedFields', response.data.selected_fields);
                    here.$store.dispatch('updateFilterSettings', response.data.filter_settings);
                    here.$nextTick(function() {
						jQueryForSelect2('.filter-settings select').trigger('change');
					});
                })
                .catch(function (error) {
                    console.error(error);
                })
        },
        dispatchRunSearchEvent: function(page) {
        	// Update the select2 dropdown for the main channel_id dropdown
			jQueryForSelect2('select.channel_id').val(this.$store.state.channel_id).trigger('change');

			// Dispatch the search
			this.$store.dispatch('runSearch', page);
        },
    },
    mounted: function () {
    }
});

/**
 * Save Search Form
 * @type {CombinedVueInstance<V, {label: string}, Object, Object, Record<never, any>>}
 */
var vmSaveSearchForm = new Vue({
	el: '.modal-saveSearch',
	name: 'Save Search Form',
	delimiters: ['@{', '}'],
	store: store,
	data: {
		label: '',
	},
	computed: {
		...mapState([
			'lang',
		]),
		...mapGetters([
		]),
	},
	methods: {
		saveSearch: function(context, label) {

			this.$store.dispatch('updateCSRFToken');

			var here = this;

			$('.modal-saveSearch').find('button.btn').addClass('work');

			var postData = qs.stringify({
				channel_id: this.$store.state.channel_id,
				rows: this.$store.state.rows,
				limit: this.$store.state.limit,
				order_by: this.$store.state.order_by,
				sort: this.$store.state.sort,
				csrf_token: this.$store.state.csrf_token,
				label: this.label,
			});

			axios.post(buildUrl('/cp/addons/settings/zenbu/save_search'), postData)
				.then(function (response) {
					here.$store.dispatch('updateSavedSearches', response.data.saved_searches);
					$('.modal-saveSearch').find('button.btn').removeClass('work');
					$('.m-close').click();
				})
				.catch(function (error) {
					console.error(error);
					$('.modal-saveSearch').find('button.btn').removeClass('work');
				})
		},
	},
	mounted: function () {
	}
});

/**
 * Saved Search Manager
 * @type {CombinedVueInstance<V, {label: string}, Object, Object, Record<never, any>>}
 */
var vmSavedSearchManager = new Vue({
	el: '.modal-savedSearchesManager',
	name: 'Saved Search Manager',
	delimiters: ['@{', '}'],
	store: store,
	data: {
		label: '',
	},
	computed: {
		...mapState([
			'lang',
			'saved_searches',
		]),
		...mapGetters([
		]),
	},
	methods: {
	},
	mounted: function () {
	}
});

/**
 * Permissions
 * @type {CombinedVueInstance<V, {label: string}, Object, Object, Record<never, any>>}
 */
var vmPermissions = new Vue({
	el: '.modal-permissions',
	name: 'Permissions',
	delimiters: ['@{', '}'],
	store: store,
	data: {
		label: '',
	},
	computed: {
		...mapState([
			'lang',
		]),
		...mapGetters([
		]),
	},
	methods: {
	},
	mounted: function () {
	}
});

/**
 * Top-right actions (Dropdowns for Display Settings, Permissions, Saved Searches...)
 * @type {CombinedVueInstance<V, {lang, csrf_token: *, channel_id: string, rows: string[][], limit: number}, Object, Object, Record<never, any>>}
 */
self.vmActions = new Vue({
	name: 'Zenbu Actions',
	el: '#zenbu-actions',
	store: store,
	delimiters: ['@{', '}'],
	data: {
	},
	mixins: [],
	computed: {
		...mapState([
			'saved_searches',
		]),
		total_saved_searches: function() {
			return _.size(this.$store.state.saved_searches);
		}
	},
	methods: {
		fetch_filters: function(search_id) {

			this.$store.dispatch('updateCSRFToken');

			let here = this;

			let postData = qs.stringify({
				search_id: search_id,
				csrf_token: this.$store.state.csrf_token,
			});

			axios.post(buildUrl('/cp/addons/settings/zenbu/fetch_saved_search_filters'), postData)
				.then(function (response) {
					here.$emit('channelChanged', response.data.channel_id);
					here.$store.commit('updateChannelId', response.data.channel_id);
					here.$store.commit('updateRows', response.data.rows);
					here.$store.commit('updateLimit', response.data.limit);
					here.$store.commit('updateOrderBy', response.data.order_by);
					here.$store.commit('updateSort', response.data.sort);

					vmFilters.$emit('loadFromSavedSearches');

					here.$nextTick(function() {
						here.$store.commit('updateChannelId', response.data.channel_id);
						here.$store.dispatch('runSearch');
					})

				})
				.catch(function (error) {
					console.error(error);
				})
		},
		prepareDisplaySettingsModelContent: function() {
			// Update the select2 display for the Display Settings' channel_id dropdown
			let value = this.$store.state.channel_id;
			jQueryForSelect2('select.display_settings_channel_id').val(value).trigger('change');
		},
	},
	watch: {
	},
	mounted: function() {
	},
});

$(document).ready(function() {
	// jQuery code would go here
});

