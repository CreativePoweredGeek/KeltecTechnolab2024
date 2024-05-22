A custom plugin for the SuperAdmin ONLY to manage varios data removal functions
**BACK UP THE DATABASE BEFORE USING ANY OF THESE TOOLS**

All of these functions are stored with an exit() at the top. Comment that out before using

# delete_chnl_data
This pliugin deletes channel Data for the channel to be removed You must lookup the field IDs for the fields to remove data from for the Channel

__Parameters__:

* channel_id = Channel ID of the channel we are removing data for
* table_id = Table ID of the field

__Use__:
{exp:admintools:delete_chnl_data channel_id="" table_id=""}


# delete_channel_titles
This will delete all Channel Title entries for a channel
Run this AFTER you have run delete_chnl_data()
__Parameters__:

* channel_id = Channel ID of the channel we are removing data for

__Use__:
{exp:admintools:delete_channel_titles channel_id=""}