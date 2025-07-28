BX.namespace('Webmatrik.Interface.Crm.Leads');

BX.Webmatrik.Interface.Crm.Leads = {
    init: function () {
		console.log('new button');

		BX.delegate(this.printButton, this)
    },
    printButton: function() {
        console.log('button print')
    }
}