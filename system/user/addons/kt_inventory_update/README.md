This is a custom plugin to auto reduce warehouse inventories after a purchase has been made. The purpose is simply to manage customer expectation on availability and to assist in creating accurate ship dates.

The inventories are reconciled by the RDI sync script with the AccountMate database which runs to update the online database after an order is completed and shipped in VAM. Any changes to the order (cancelations, warehouse changes or otherwise) will be updated & reflected online after the RDI scrip runs.

This plugin is placed in the "_store/order-info_" page which is displayed after the online purchase has completed.

