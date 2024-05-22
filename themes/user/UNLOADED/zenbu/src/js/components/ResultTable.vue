<template>
    <div class="scrollwrapper">
        <table class="data fullwidth mainTable resultsTable" cellpadding="0" cellspacing="0" width="100%" border="0">
            <thead>
				<tr>
					<th class="text--center" width="1%">
						<input type="checkbox" v-model="selectAllChecked" @change="toggleSelected">
					</th>

					<th v-for="column, colIndex in columns" :class="{ clickable: isClickable(column.id) }" @click="setOrderAndSort(column.id)">
						{{column.label}} <i class="icon fa" :class="getSortClass(column.id)"></i>
					</th>

					<th v-for="column, colIndex in fallbackColumns" :class="{ clickable: isClickable(column.id) }" @click="setOrderAndSort(column.id)" v-if="no_columns">
						{{column.label}} <i class="icon fa" :class="getSortClass(column.id)"></i>
					</th>

				</tr>
				<tr v-if="no_columns && ! searching">
					<th :colspan="total_table_columns" class="text--center">
						<em><i class="fa fa-lg fa-info-circle"></i> <span v-html="lang.open_display_settings_to_add_columns"></span></em>
					</th>
				</tr>
            </thead>

            <tbody>

                <tr v-for="entry, index in entries" :class="{ odd: index % 2 == 0, even: index % 2 !=0, dimmed: searching }" v-if="! searching && ! no_results">
                    <td class="clickable text--center">
                        <input type="checkbox" name="selection[]" v-model="selected" :value="entry['entry_id']"
                               :data-title="entry['title_raw']"
                               :data-channel-id="entry['channel_id']"
                               :data-confirm="'Entry ID <code>' + entry['entry_id'] + '</code>: <b>' + entry['title_raw'] + '</b>'"
                        />
						<!--<span v-if="no_columns" v-html="getNoColumnsText(entry)"></span>-->
                    </td>

                    <td v-for="column, colIndex in columns" v-html="getEntryContent(entry, column)" :class="{ 'zenbuWrap': isContentLong(entry, column) }"></td>
                    <td v-if="no_columns" v-for="column, colIndex in fallbackColumns" v-html="getEntryContent(entry, column)" :class="{ 'zenbuWrap': isContentLong(entry, column) }"></td>
                </tr>

                <tr v-if="! searching && no_results" class="no-results">
                    <td :colspan="total_table_columns">No results</td>
                </tr>

				<tr v-if="searching && ! no_results" class="no-results">
					<td :colspan="total_table_columns"><i class="fa fa-2x fa-spinner fa-spin"></i></td>
				</tr>

                <tr v-if="search_error" class="no-results has-errors">
                    <td :colspan="total_table_columns"><i class="fa fa-2x fa-spinner fa-spin" v-if="searching"></i>
						<div v-if="! searching">
							<h1><i class="fa fa-exclamation-triangle"></i> {{lang.ran_into_an_error}}</h1>
							<p>
								<button type="button" class="button" @click="toggleShowError" v-text="hood_state"></button>
							</p>
							<div class="error-details" v-show="show_error_details">
								<p v-text="lang.let_the_developer_know"></p>
								<div v-html="search_error.data"></div>
							</div>
						</div>
					</td>
                </tr>

            </tbody>
        </table>
    </div>
</template>

<script>

	module.exports = {
        name: "Results-Table",
	    props: {
            rowIndex: {
                type: Number,
            },
            entries: {
            },
		},
	    data: function() {

            // var columns = {};

            //     'entry_id': this.$parent.lang.entry_id,
            //     'title': this.$parent.lang.title,
            //     'url_title': this.$parent.lang.url_title,
            //     'entry_date': this.$parent.lang.entry_date,
            //     'categories': this.$parent.lang.categories,
            //     'channel': this.$parent.lang.channel,
            //     'field_id_7': 'Field ID 7',
            

			return {
                // columns: columns,
                order_by: this.$store.state.order_by,
                sort: this.$store.state.sort,
                selected: [],
                selectAllChecked: false,
                mounted: false,
				show_error_details: false,
				lang: this.$store.state.lang,
				fallbackColumns: [
					{ id: 'entry_id', label: this.$parent.lang['entry_id'] },
					{ id: 'title', label: this.$parent.lang['title'] },
					{ id: 'status_id', label: this.$parent.lang['status_id'] },
					{ id: 'author_id', label: this.$parent.lang['author_id'] },
					{ id: 'entry_date', label: this.$parent.lang['entry_date'] },
				]
		  	}
        },
        methods: {
            toggleSelected: function() {
                this.selectedAllChecked = ! this.selectedAllChecked;
                if(this.selectedAllChecked)
                {
                    this.selected = _.map(this.entries, function(entry) {
                        return entry.entry_id;
                    });
                }
                else
                {
                    this.selected = [];
                }
            },
            setOrderAndSort: function(col) {
            	if(this.isClickable(col))
                {
                    this.order_by = col;
                    this.sort = this.sort == 'asc' ? 'desc' : 'asc';
                    this.$store.dispatch('updateOrderBy', col);
                    this.$store.dispatch('updateSort', this.sort);
                    this.$store.dispatch('runSearch');
                }
            },
            getSortClass: function(col) {
                if(this.order_by == col)
                {
                    if(this.sort == 'desc')
                    {
                        return {
                            'fa-sort-amount-desc': true,
                            'fa-sort-amount-asc': false,
                        };
                    }
                    else
                    {
                        return {
                            'fa-sort-amount-desc': false,
                            'fa-sort-amount-asc': true,
                        };
                    }
                }
            },
            getEntryContent: function(entry, column)
            {
            	if(entry[column.id])
                {
                	return entry[column.id];
                }

                return entry['field_id_' + column.id];
            },
            isContentLong: function(entry, column) {
                let contentLength = this.getEntryContent(entry, column) ? this.getEntryContent(entry, column).length : 0;

                if(contentLength > 100)
                {
                	return true;
                }

                return false;
			},
			isClickable: function(col) {
            	if(_.isInteger(_.toNumber(col)))
                {
                    return true;
                }

                if(_.includes(['title', 'entry_id', 'status_id', 'author_id', 'channel_id', 'url_title', 'entry_date', 'edit_date', 'expiration_date', 'preview'], col))
                {
                	return true;
                }

                return false;
            },
			toggleShowError: function() {
            	return this.show_error_details = ! this.show_error_details;
			},
			getNoColumnsText: function(entry) {
            	return '#' + entry.entry_id + ': ' + entry.title;
			},
        },
        computed: {
            columns: function() {
                var here = this;
                return _.map(this.$store.getters.result_columns, function(col) {
                	if(! _.isUndefined(here.$parent.lang[col]) )
                    {
                    	// We have a language string for the column.

                    	label = here.$parent.lang[col];
                    }
                    else
                    {
                    	// Trying to look if there's field data
						// in the display settings from which we could
						// extract the field_label.

                    	found = _.find(here.$store.state.display_settings, function(ds) {
                    		return ds.fieldId == col;
                        });

                    	if(found && ! _.isUndefined(found.field_label))
                        {
                        	label = found.field_label;
                        }
                        else
                        {
                        	label = col;
                        }
                    }

                    return { id: col, label: label };
                });
            },
			no_columns: function() {
            	return _.size(this.columns) === 0;
			},
			no_results: function() {
            	return ! this.searching && _.size(this.entries) === 0 && ! this.search_error;
			},
			search_error: function() {
				return this.$store.state.search_error;
			},
			searching: function() {
                return this.$store.state.searching;
            },
			total_table_columns: function() {
            	let total_columns = _.size(this.columns);

            	if(total_columns === 0)
				{
					return _.size(this.fallbackColumns) + 1;
				}

            	return total_columns + 1; // + 1 is for the checkbox column
			},
			hood_state: function() {
            	if(this.show_error_details)
				{
					return this.lang.close_the_hood;
				}

				return this.lang.open_the_hood;
			}
        },
        watch: {
            selected: function() {
            	this.$store.commit('updateSelectedEntries', this.selected);
            },
        },
        mounted: function() {
            var here = this;
            this.mounted = true;
        }
	}
</script>

<style scoped>

    div.scrollwrapper {
        width: 100%;
        overflow-x: auto;
    }

    .clickable {
        cursor: pointer;
    }

    .dimmed {
        opacity: 0.5;
    }

    td.zenbuWrap {
        white-space: normal;
    }

	tr.has-errors td {
		text-align: left;
	}

	tr.has-errors td h1,
	tr.has-errors td h1 i {
		color: red;
		padding: 0;
	}

</style>