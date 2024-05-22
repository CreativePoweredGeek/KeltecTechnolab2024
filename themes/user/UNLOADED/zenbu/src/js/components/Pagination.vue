<template>
    <div>
        <div class="paginate" aria-label="Page navigation" v-cloak v-if="pagination">
            <!--<span class="pagination-results" v-show="pagination.total > 0">Showing {{pagination.from}}-{{pagination.to}} of {{pagination.total}} {{pagination.total == 1 ? 'result' : 'results'}}</span>-->
            <ul>
                <li v-if="pagination.first && pagination.current_page != 1">
                    <a :href="pagination.first" aria-label="First" @click.prevent="dispatchRunSearchEvent(1)">
                        <span aria-hidden="true"><i class="fa fa-angle-double-left"></i></span>
                    </a>
                </li>

                <li v-if="pagination.prev">
                    <a :href="pagination.prev" aria-label="Previous" @click.prevent="dispatchRunSearchEvent((pagination.current_page - 1 >= 1 ? pagination.current_page - 1 : 1))">
                        <span aria-hidden="true"><i class="fa fa-angle-left"></i></span>
                    </a>
                </li>

                <li v-if="pagination" v-for="(url, page) in pagination.pages"><a :href="url" :class="{act: page == pagination.current_page}" @click.prevent="dispatchRunSearchEvent(page)">{{page}}</a></li>

                <li v-if="pagination.next">
                    <a :href="pagination.next" aria-label="Next" @click.prevent="dispatchRunSearchEvent(pagination.current_page + 1)">
                        <span aria-hidden="true"><i class="fa fa-angle-right"></i></span>
                    </a>
                </li>

                <li v-if="pagination.last && pagination.current_page != last_page_number">
                    <a :href="pagination.last" aria-label="Last" @click.prevent="dispatchRunSearchEvent(last_page_number)">
                        <span aria-hidden="true"><i class="fa fa-angle-double-right"></i></span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="paginate-info" v-html="pagination_info"></div>
    </div>
</template>

<script>
    module.exports = {
        name: "Pagination",
        props: {
            pagination: {
                type: null,
                default: function() {
                    return [];
                }
            },
            list: {
                type: null,
                default: function() {
                    return [];
                }
            },
        },
        data: function() {
            return {
				// current_page: this.pagination.current_page
            }
        },
        methods: {
            dispatchRunSearchEvent: function(page) {
                this.$store.dispatch('runSearch', page);
            },
        },
        computed: {
            last_page_number: function() {
                return this.pagination ? _.findLastKey(this.pagination.pages) : null;
            },
            from: function() {

            	if(! this.pagination)
                {
                	return '';
                }

            	if(this.pagination.current_page == 1)
                {
                	return 1;
                }

                return ((this.pagination.current_page - 1) * this.$store.state.limit) + 1;
            },
			to: function() {

            	if(! this.pagination)
                {
                	return '';
                }

            	if(this.pagination.current_page == 1)
				{
					return this.$store.state.limit;
				}

				let calculated_to = this.pagination.current_page * this.$store.state.limit;

				return calculated_to > this.pagination.total_count ? this.pagination.total_count : calculated_to;
			},
            pagination_info: function() {
            	if(_.size(this.pagination) == 0)
                {
                	return '';
					// let lang = this.$store.state.lang.showing_all_x_results;
					// lang = lang.replace('%x', _.size(this.list));
                	// return lang;
                }

                let lang = this.$store.state.lang.showing_x_of_x;
				lang = lang.replace('%x', this.from);
                lang = lang.replace('%y', this.to);
                lang = lang.replace('%z', this.pagination.total_count);
                return lang;
            },
        }
    }
</script>

<style scoped>
    .paginate-info {
        margin: 5px 10px;
        float: left;
    }
</style>

