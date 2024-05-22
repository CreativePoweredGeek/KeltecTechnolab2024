<template>
	<div class="">
		<fieldset class="col-group">
			<div class="setting-field w-16 last">
				<div class="col w-16">
					<div class="tbl-list-wrap">
						<ul class="tbl-list">
							<li v-sortable="sortableOptions">
								<div class="tbl-row" v-for="search in savedSearches" :key="search.order">
									<div class="reorder"></div>
									<div class="txt">
										<input type="text" v-model="search.label" @keyup="updateSavedSearches()">
									</div>
									<div class="check-ctrl"><input type="checkbox" name="selectedItems[]" :value="search.id" v-model="selectedItems"></div>
								</div>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</fieldset>
		<fieldset class="tbl-bulk-act">
			<button class="btn submit" @click="deleteSavedSeaches" v-if="displayDeleteButton">Delete</button>
		</fieldset>
	</div>
</template>

<script>
	module.exports = {
		name: "Saved-Search-Manager",
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
				selectedItems: [],
			}
		},
		methods: {
			/**
			 * Updates the selected fields (on store).
			 * @param e event   The onEnd sortable.js event
			 */
			updateOrder: function(e) {
				let search = this.savedSearches[e.oldIndex];
				this.savedSearches.splice(e.oldIndex, 1);
				this.savedSearches.splice(e.newIndex, 0, search);

				let searches = _.map(this.savedSearches, function(s) {
					return s;
				});

				// Save saved searches
				this.updateSavedSearches(searches);
			},
			fetchSavedSearches: function() {
				this.$store.dispatch('updateCSRFToken');

				let here = this;

				var postData = qs.stringify({
					csrf_token: this.$store.state.csrf_token
				});

				axios.post(buildUrl('/cp/addons/settings/zenbu/fetch_saved_searches'), postData)
					.then(function (response) {
						here.$store.dispatch('updateSavedSearches', response.data.saved_searches);
					})
					.catch(function (error) {
						console.error(error);
						here.$store.dispatch('updateSavedSearches', []);
					})
			},
			updateSavedSearches: _.debounce(function(saved_searches) {
				// Note: Debouncing since _.throttle fights with what you are writing vs what is saved and returned.

				// Don't have saved searches list provided?
				// (eg. when triggering from input field)
				// Use the currently computed list instead.

				if(! saved_searches)
				{
					saved_searches = this.savedSearches;
				}

				this.$store.commit('updateDataSaveState', 'saving');
				this.$store.dispatch('updateCSRFToken');

				let here = this;
				this.$nextTick(function() {
					var postData = qs.stringify({
						csrf_token: here.$store.state.csrf_token,
						saved_searches: saved_searches,
					});

					axios.post(buildUrl('/cp/addons/settings/zenbu/update_saved_searches'), postData)
						.then(function(response) {
							// Dispatching back up to store has a problem here: it refreshes an input
							// to what was saved, but if you were still typing in the meantime, that gets
							// erased in your input field. That leads to a weird "jerkiness" in typing, as well
							// as very-easy-to-make typing mistakes. Commenting out, visually the data looks as
							// it should already anyway.
							// here.$store.dispatch('updateSavedSearches', response.data.saved_searches);
							here.$store.commit('updateDataSaveState', 'saved');
						})
						.catch(function(error) {
							console.error(error);
							here.$store.dispatch('updateSavedSearches', []);
							here.$store.commit('updateDataSaveState', 'error');
						})
				});
			}, 500),
			deleteSavedSeaches: function() {
				let answer = confirm(this.$store.state.lang.delete_saved_searches_confirm);

				if(answer)
				{
					this.$store.dispatch('updateCSRFToken');

					let here = this;

					var postData = qs.stringify({
						csrf_token: this.$store.state.csrf_token,
						selected_items: this.selectedItems,
					});

					axios.post(buildUrl('/cp/addons/settings/zenbu/delete_saved_searches'), postData)
						.then(function (response) {
							here.$store.dispatch('updateSavedSearches', response.data.saved_searches);
						})
						.catch(function (error) {
							console.error(error);
							here.$store.dispatch('updateSavedSearches', []);
						})
				}
			},

		},
		computed: {
			savedSearches: function() {
				return this.$store.state.saved_searches;
			},
			sortableOptions: function() {
				let here = this;
				return {
					animation: 150,
					onEnd: function(e) {
						here.updateOrder(e);
					},
					ghostClass: 'sortable-ghost',
					handle: '.reorder',
				}
			},
			displayDeleteButton: function() {
				return _.size(this.selectedItems) > 0;
			},
		},
		mounted: function() {
			this.fetchSavedSearches();
		}

	};
</script>

<style scoped>
	.tbl-list li input {
		margin: 0;
	}
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