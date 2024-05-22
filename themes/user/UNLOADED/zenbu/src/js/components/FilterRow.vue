<template>
	<div class="col-group zenbu-filters" :class="'row-index-' + rowIndex">

        <div class="col w-4 first">
            <select2 :options="options1" v-model="filter1" :has-optgroups="true" minimum-results-for-search="0"></select2>
        </div>
        <div class="col w-4 second">
			<select2 :options="options2" v-model="filter2"></select2>
        </div>
        <div class="col w-4 third">

            <span class="spacer" v-if="thirdFilterType == null" v-cloak>&nbsp;</span>

            <input type="text" @keydown.prevent.enter="runSearchOnly" v-model="filter3" v-if="thirdFilterType == 'input'">

            <input type="date" @keydown.prevent.enter="runSearchOnly" v-model="filter3" v-if="thirdFilterType == 'date'">

            <flatpickr @keydown.prevent.enter="runSearchOnly" v-model="filter3" v-if="thirdFilterType == 'dateRange'" mode="range" :default-date="filter3"></flatpickr>

            <select2 :options="options3" v-model="filter3" v-if="thirdFilterType == 'select'" :has-optgroups="thirdFilterHasOptgroups" minimum-results-for-search="7" :allow-clear="true">
                <option value="">-- Select --</option>
            </select2>
        </div>
        <div class="col w-1">
            <button type="button" class="add" @click="addRow"><i class="fa fa-2x fa-plus-circle"></i></button>
            <button type="button" class="remove" @click="removeRow"><i class="fa fa-2x fa-minus-circle" v-if="totalRows > 1"></i></button>
        </div>
        <div class="col w-3" v-if="debug_mode">
            {{rowData}}
        </div>
    </div>
</template>

<script>
	module.exports = {
        name: "Search-Row",
	    props: {
            rowIndex: {
                type: Number,
            },
            options1: {

            },
		},
	    data: function() {

			return {
                options3: [],

				debug_mode: this.$store.state.debug_mode,
		  	}
        },
        methods: {
            runSearchOnly: function() {
                this.$store.dispatch('runSearch');
            },
            addRow: function() {
                // Squeeze a new row right after this one.
                this.$parent.rows.splice(this.rowIndex + 1, 0, [this.filter1, this.filter2, this.filter3]);
				this.$store.dispatch('runSearch');
            },
            removeRow: function() {
                let here = this;

                // Don't remove the row if there's only one left.
                if(_.size(this.$store.state.rows) > 1)
                {
                    this.$parent.rows.splice(this.rowIndex, 1);
					this.$store.dispatch('runSearch');
                    this.$nextTick(function() {
                        jQueryForSelect2('.zenbu-filters select').trigger('change');
                    });
                }
            },
            refreshSecondFilter: function() {
				let here = this;
            	let optionFallbackFilter2 = _.first(this.options2).id;
				let option2Keys = _.map(this.options2, function(o) {
					return o.id;
				});
				let selectedFilter2Value = _.includes(option2Keys, this.rowData[1]) ? this.rowData[1] : optionFallbackFilter2;
				this.$store.commit('updateRow', { index: this.rowIndex, filterPos: 1, value: selectedFilter2Value });

				this.$nextTick(function() {
					$('.row-index-' + here.rowIndex + ' .second select').val(selectedFilter2Value).trigger('change');
				});
			},
			refreshThirdFilter: function() {
            	let here = this;
				this.$nextTick(function() {

					if(here.thirdFilterType == 'select' && _.size(here.options3) > 0)
					{
						let optionFallbackFilter3 = _.first(here.options3).id;
						let option3Keys = _.map(here.options3, function(o) {
							return o.id;
						});
						let selectedFilter3Value = _.includes(option3Keys, this.rowData[2]) ? this.rowData[2] : optionFallbackFilter3;
						this.$store.commit('updateRow', { index: this.rowIndex, filterPos: 2, value: selectedFilter3Value });
						$('.row-index-' + here.rowIndex + ' .third select').val(selectedFilter3Value).trigger('change');

						// Run the search again: this update may be happening after
						// the runSearch action has been dispatched.
						this.$store.dispatch('runSearch');
					}

				});
			},
            buildIndentedDropdownOptions: function(cats, depth) {
                let here = this;
                var returnedOptions = [];

                _.each(cats, function(cat, id) {
                    var spacer = _.repeat('&nbsp;', depth * 2) + ' ';
                    returnedOptions.push({id: id, text: cat.name ? spacer + cat.name : spacer + cat});

                    if(cat.children) {
                        depth++;
                        returnedOptions = _.union(returnedOptions, here.buildIndentedDropdownOptions(cat.children, depth));
                    }
                });

                return returnedOptions;
            },
            updateOptions3: function() {
            	let here = this;

				here.$store.dispatch('updateCSRFToken');

				switch(this.filter1)
				{
					case 'status_id':
						axios.post(buildUrl('/cp/addons/settings/zenbu/fetch_statuses'), qs.stringify({channel_id: this.$store.state.channel_id, csrf_token: this.$store.state.csrf_token}))
							.then(function(response) {

								let returnedOptions = _.map(response.data, function(item) {
									return {id: item.status_id, text: _.upperFirst(item.status) };
								});

								here.options3 = returnedOptions;
								here.refreshThirdFilter();
							})
							.catch(function(error) {
								console.error(error);
							})
						break;
					case 'author_id':
						axios.post(buildUrl('/cp/addons/settings/zenbu/fetch_authors'), qs.stringify({channel_id: this.$store.state.channel_id, csrf_token: this.$store.state.csrf_token}))
							.then(function(response) {

								let returnedOptions = _.map(response.data, function(item) {
									return {id: item.member_id, text: item.screen_name };
								});

								here.options3 = returnedOptions;
								here.refreshThirdFilter();
							})
							.catch(function(error) {
								console.error(error);
							})
						break;
					case 'category_id':
						axios.post(buildUrl('/cp/addons/settings/zenbu/fetch_categories'), qs.stringify({channel_id: this.$store.state.channel_id, csrf_token: this.$store.state.csrf_token}))
							.then(function(response) {
								var returnedOptions = {};

								_.each(response.data, function(group) {
									returnedOptions[group.group_name] = here.buildIndentedDropdownOptions(group.tree, 0);
									// Do something with group.tree
								});

								here.options3 = returnedOptions;
								here.refreshThirdFilter();
							})
							.catch(function(error) {
								console.error(error);
							})
						break;
					default:
						here.options3 = [];
						break;
				}
            },
        },
        computed: {
        	totalRows: function() {
                return _.size(this.$store.state.rows);
            },
			rowData: function() {
            	return this.$store.state.rows[this.rowIndex];
			},
			filter1: {
            	get: function() {
					return this.$store.state.rows[this.rowIndex][0];
				},
				set: function(value) {
            		// console.log('Row: ' + this.rowIndex + ', setting filter1', value);
					this.$store.commit('updateRow', { index: this.rowIndex, filterPos: 0, value: value });
					// Make sure the 2nd filter has something selected
					this.refreshSecondFilter();
					// Get options for 3rd filter (ajax request). Then, have something selected after ajax is complete.
					this.updateOptions3();
					// If querying is marked as skiped, don't trigger it.
                    // This gets set back to FALSE after a search is done.
					if(this.$store.state.skip_run_search_trigger === false)
                    {
					    this.$store.dispatch('runSearch');
                    }
				},
			},
			filter2: {
				get: function() {
					return this.$store.state.rows[this.rowIndex][1];
				},
				set: function(value) {
					// console.log('Row: ' + this.rowIndex + ', setting filter2', value);
					this.$store.commit('updateRow', { index: this.rowIndex, filterPos: 1, value: value });
					// If querying is marked as skiped, don't trigger it.
                    // This gets set back to FALSE after a search is done.
					if(this.$store.state.skip_run_search_trigger === false)
                    {
					    this.$store.dispatch('runSearch');
                    }
				},
			},
			filter3: {
				get: function() {
					return this.$store.state.rows[this.rowIndex][2];
				},
				set: function(value) {
                    if(this.$store.state.debug_mode === true)
                    {
                        console.log('Row: ' + this.rowIndex + ', setting filter3', value);
                    }
					this.$store.commit('updateRow', { index: this.rowIndex, filterPos: 2, value: value });
					// If querying is marked as skiped, don't trigger it.
                    // This gets set back to FALSE after a search is done.
					if(this.$store.state.skip_run_search_trigger === false)
                    {
					    this.$store.dispatch('runSearch');
                    }
				},
			},
			// options2 is computed locally within this component
			options2: function() {

                if(this.filter1 === 'title' || this.filter1 === 'url_title')
				{
					return this.$parent.buildDropdownArray(['contains', 'doesntContain', 'beginsWith', 'doesntBeginWith', 'endsWith', 'doesntEndWith', 'containsExactly']);
				}
				else if(this.filter1 === 'entry_id')
				{
					return this.$parent.buildDropdownArray(['is', 'isNot']);
				}
				else if(this.filter1 === 'status_id')
				{
					return this.$parent.buildDropdownArray(['is', 'isNot']);
				}
				else if(this.filter1 === 'author_id')
				{
					return this.$parent.buildDropdownArray(['is', 'isNot']);
				}
				else if(this.filter1 === 'category_id')
				{
					return this.$parent.buildDropdownArray(['is', 'isNot']);
				}
				else if(this.filter1 === 'sticky')
				{
					return this.$parent.buildDropdownArray(['isOn', 'isOff']);
				}
				else if(this.filter1 === 'entry_date' || this.filter1 === 'edit_date' || this.filter1 === 'expiration_date')
				{
					return this.$parent.buildDropdownArray(['betweenDates', 'inTheLast1', 'inTheLast3', 'inTheLast7', 'inTheLast30', 'inTheLast180', 'inTheLast365', 'inTheNext1', 'inTheNext3', 'inTheNext7', 'inTheNext30', 'inTheNext180', 'inTheNext365']);
				}
				else if(_.isNumber(_.toNumber(this.filter1)))
				{
					let here = this;
					let field = _.find(this.$store.state.custom_fields, function(cf) {
						return cf.field_id == here.filter1;
					});

					if(field)
                    {
                        if(field.field_type == 'toggle')
                        {
                            return this.$parent.buildDropdownArray(['isOn', 'isOff']);
                        }

                        if(field.field_type == 'relationship')
                        {
                            return this.$parent.buildDropdownArray(['contains', 'doesntContain', 'isEmpty', 'isNotEmpty']);
                        }

                        if(field.field_type == 'date')
                        {
                            return this.$parent.buildDropdownArray(['betweenDates', 'inTheLast1', 'inTheLast3', 'inTheLast7', 'inTheLast30', 'inTheLast180', 'inTheLast365', 'inTheNext1', 'inTheNext3', 'inTheNext7', 'inTheNext30', 'inTheNext180', 'inTheNext365']);
                        }
                    }

					return this.$parent.buildDropdownArray(['contains', 'doesntContain', 'beginsWith', 'doesntBeginWith', 'endsWith', 'doesntEndWith', 'containsExactly', 'isEmpty', 'isNotEmpty']);
				}
				else
				{
					return this.$parent.buildDropdownArray(['contains', 'doesntContain', 'beginsWith', 'doesntBeginWith', 'endsWith', 'doesntEndWith', 'containsExactly', 'isEmpty', 'isNotEmpty']);
					return [
						{id: 'contains', text: 'contains*'},
						{id: 'is', text: 'is*'},
					];
					return this.$parent.buildDropdownArray(['contains', 'is']);
				}

			},
			thirdFilterType: function() {

        		if(this.filter2 === 'isEmpty' || this.filter2 === 'isNotEmpty')
				{
					return null;
				}

				if(this.filter1 === 'title' || this.filter1 === 'url_title' || this.filter1 === 'entry_id')
				{
					return 'input';
				}
				else if(this.filter1 === 'status_id' || this.filter1 === 'author_id' || this.filter1 === 'category_id')
				{
					return 'select';
				}
				else if(this.filter1 === 'entry_date' || this.filter1 === 'edit_date' || this.filter1 === 'expiration_date' || this.filter1 == 'sticky')
				{
				    if(this.filter2 === 'betweenDates')
                    {
    					return 'dateRange';
                    }

				    return null;
				}
				else if(_.isNumber(_.toNumber(this.filter1)))
				{
					let here = this;
					let field = _.find(this.$store.state.custom_fields, function(cf) {
						return cf.field_id == here.filter1;
					});

					if(field)
					{
						if(field.field_type == 'toggle' || field.field_type == 'date')
						{
                            if(this.filter2 === 'betweenDates')
                            {
                                return 'dateRange';
                            }

							return null;
						}
					}

					return 'input';
				}
				else
				{
					return 'input';
				}
            },
			thirdFilterHasOptgroups: function() {
            	switch(this.filter1) {
					case 'category_id':
					    return true;
					break;
					default:
						return false;
                    break;
                }
            },
        },
        watch: {
            thirdFilterType: function(thirdFilterType) {
                if(thirdFilterType == 'dateRange')
                {
                    // this.$nextTick(function() {
                    //     flatpickr('.dateRange', {});
                    // })
                }
            }
        },
        mounted: function() {
            let here = this;

            here.$store.dispatch('updateCSRFToken');

            this.$parent.$on('channelChanged', function(channel_id) {
            	here.$store.dispatch('updateCSRFToken');
            });

			this.$parent.$on('loadFromSavedSearches', function() {

				here.$store.commit('updateSkipRunSearchTrigger', true);
				if(here.$store.state.debug_mode === true)
                {
                    console.log('--- Loaded from saved searches! ---');
                }

				here.$nextTick(function() {
					// .trigger('change') should trip filter1's set() method,
					// which then triggers filterChanged();
					// Should also update the select2 display.
					// $('.row-index-' + here.rowIndex + ' .first select').val(here.$store.state.rows[here.rowIndex][0]).trigger('change');

					// Refresh the following select2 displays as well
					jQueryForSelect2('select.channel_id').val(here.$store.state.channel_id).trigger('change');
					jQueryForSelect2('select.limit').val(here.$store.state.limit).trigger('change');

				});
			});

            this.$nextTick(function() {
                flatpickr('.dateRange', { wrap: true });
            });

			// Refresh some options and selections
			// Typically when the row is loaded (and then mounted here) from a saved search.

			this.refreshSecondFilter();
			this.updateOptions3();


        }
	}
</script>

<style>
.zenbu-filters .col {
    padding-left: 0;
}
</style>