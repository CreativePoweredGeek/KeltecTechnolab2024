<template>
    <small v-if="state_message" v-html="state_message" :class="state_classes"></small>
</template>

<script>
    module.exports = {
        name: "Modal-Save-Status",
        props: {

        },
        data: function() {
            return {
            }
        },
        methods: {
            delayToClear: _.debounce(function() {
            	this.$store.commit('updateDataSaveState', null);
            }, 2000),
        },
        computed: {
            state_message: function() {
                if(this.$store.state.data_save_state == 'saving')
                {
                	return '<i class="fa fa-spinner fa-spin"></i> ' + this.$store.state.lang.saving;
                }

				if(this.$store.state.data_save_state == 'saved')
				{
					this.delayToClear();
					return this.$store.state.lang.saved;
				}

				if(this.$store.state.data_save_state == 'error')
				{
					this.delayToClear();
					return this.$store.state.lang.error_see_console;
				}

				return null;
            },
            state_classes: function() {
				if(this.$store.state.data_save_state == 'saved')
                {
                    return 'success';
                }

				if(this.$store.state.data_save_state == 'error')
				{
					return 'error';
				}
            },
        }
    }
</script>

<style scoped>
    small {
        float: right;
        opacity: 0.5;
    }

    small.success {
        color: green;
    }

    small.error {
        color: red;
    }
</style>

