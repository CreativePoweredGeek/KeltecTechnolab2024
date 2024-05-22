<template>
	<div class="">
		<fieldset class="col-group">
			<h2 v-html="lang.copy_to_member_groups">Copy to member groups</h2>
			<p class="setting-txt">
				<em v-text="lang.copy_to_member_groups_warning"></em>
			</p>

			<table cellspacing="0">
				<thead>
					<tr>
						<th width="80%" v-html="lang.member_group_name"></th>
						<th width="20%"></th>
					</tr>
				</thead>
				<tbody>

					<tr v-for="group_title, group_id in member_groups">
						<td v-html="group_title"></td>
						<td>
							<button class="btn btn-block" @click="showConfirmButtons(group_id)" v-if="selected_member_group_id != group_id">Copy</button>
							<div class="confirm" v-if="selected_member_group_id == group_id">
								<button class="btn action btn-primary" @click="saveDisplaySettings(group_id)" :title="lang.confirm_copy"><i class="fa fa-check"></i></button>
								<button class="btn btn-secondary" @click="selected_member_group_id = null" :title="lang.cancel_copy"><i class="fa fa-times"></i></button>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</fieldset>
	</div>
</template>

<script>

	module.exports = {
		name: "Display-Settings-Copy-To-Group",
		directives: {
		},
		props: {
		},
		data: function() {
			return {
				member_groups: this.$store.state.member_groups,
				selected_member_group_id: null,
				lang: this.$store.state.lang,
			}
		},
		methods: {
			saveDisplaySettings: _.throttle(function(member_group_id) {
				let here = this;
				this.$nextTick(function() {
					here.$store.dispatch('saveDisplaySettingsForGroup', {
						channel_id: here.$parent.channel_id,
						member_group_id: member_group_id
					});
					here.selected_member_group_id = null;
				});
			}, 1000),
			showConfirmButtons: function(group_id) {
				this.selected_member_group_id = group_id;
			},
		},
		computed: {
		},
		mounted: function() {

		}

	};
</script>

<style scoped>
	button.btn-block {
		display: block;
		width: 100%;
	}

	button.btn-primary {
		width: 48%;
		padding-right: 2%;
	}

	button.btn-secondary {
		width: 48%;
		background-color: #fff;
		border-color: #cdcdcd;
		border-style: solid;
		color: gray;
	}
</style>