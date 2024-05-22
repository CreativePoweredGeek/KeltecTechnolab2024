<template>
	<div class="">
		<fieldset class="col-group">
			<div class="setting-field w-16 last">
				<fieldset class="col-group" v-for="perm in permission_list">
					<div class="setting-txt col w-6">
						<h3 v-text="lang[perm]"></h3>
						<em v-html="lang[perm + '_subtext']"></em>
					</div>
					<div class="setting-field col w-10 last">
						<label class="choice mr" v-for="group in member_groups" :class="{ chosen: isEnabled(group.group_id, perm) }">
							{{group.group_title}}
							<input type="checkbox" v-model="permissions[group.group_id]" :name="'permission['+ group.group_id + ']'" :value="perm" @click="updatePermissions(group.group_id, perm)" :disabled="disableCheckbox(group, perm)">
						</label>
					</div>

				</fieldset>
			</div>
		</fieldset>
	</div>
</template>

<script>

	module.exports = {
		name: "Permissions",
		directives: {

		},
		props: {

		},
		data: function() {
			return {
				member_groups: [],
				permissions: {},
				lang: this.$store.state.lang,
				permission_list: [
					'can_admin',
					'can_access_settings',
					'edit_replace',
					'can_copy_profile',
					// 'can_view_group_searches',
					// 'can_admin_group_searches'
				],
			}
		},
		methods: {
			fetchPermissions: function() {

				this.$store.dispatch('updateCSRFToken');

				let here = this;

				var postData = qs.stringify({
					csrf_token: this.$store.state.csrf_token
				});

				axios.post(buildUrl('/cp/addons/settings/zenbu/fetch_permissions'), postData)
					.then(function (response) {
						here.member_groups = response.data.member_groups;
						here.permissions = response.data.permissions;
						_.each(here.member_groups, function(g) {
							if(_.isUndefined(here.permissions[g.group_id]))
							{
								here.$set(here.permissions, g.group_id, []);
							}
						});
						// here.$store.dispatch('updateSavedSearches', response.data.saved_searches);
					})
					.catch(function (error) {
						console.error(error);
						// here.$store.dispatch('updateSavedSearches', []);
					})
			},
			updatePermissions: function(group_id, permission) {

				this.$store.commit('updateDataSaveState', 'saving');
				this.$store.dispatch('updateCSRFToken');

				let here = this;

				this.$nextTick(function() {
					let enabled = 'n';

					if(_.includes(here.permissions[group_id], permission))
					{
						enabled = 'y';
					}

					var postData = qs.stringify({
						csrf_token: here.$store.state.csrf_token,
						group_id: group_id,
						permission: permission,
						enabled: enabled,
					});

					axios.post(buildUrl('/cp/addons/settings/zenbu/save_permissions'), postData)
						.then(function(response) {
							here.$store.commit('updateDataSaveState', 'saved');
						})
						.catch(function(error) {
							console.error(error);
							here.$store.commit('updateDataSaveState', 'error');
						})
				})
			},
			isEnabled: function(group_id, permission)
			{
				return _.includes(this.permissions[group_id], permission);
			},
			disableCheckbox: function(group, permission)
			{
				return group.group_id == 1 && permission != 'edit_replace';
			}
		},
		computed: {
		},
		mounted: function() {
			this.fetchPermissions();
		}

	};
</script>

<style scoped>
	.permission-column {
		white-space: normal;
		width: 15%;
	}
</style>