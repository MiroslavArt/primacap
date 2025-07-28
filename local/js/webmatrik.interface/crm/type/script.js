BX.namespace('Webmatrik.Interface.Crm.Type');

BX.Webmatrik.Interface.Crm.Type = {
    init: function () {
        console.log('init type');
        BX.addCustomEvent('BX.Crm.EntityEditor:onControlModeChange', BX.delegate(this.changeControlWidth, this));
    },
    changeControlWidth: function(event, data) {
        console.log(event)
        console.log(data)
        if(event._id == 'DYNAMIC_1036_details_C8_editor') {
            console.log('match');
            $('.ui-selector-dialog').first().css('width', '800px');
        }
    }
}