BX.namespace('Webmatrik.Interface.Crm.Leads');

BX.Webmatrik.Interface.Crm.Leads = {
    init: function () {
        BX.delegate(this.printButton, this)
    },
    printButton: function() {
        console.log('button print')
    }
}