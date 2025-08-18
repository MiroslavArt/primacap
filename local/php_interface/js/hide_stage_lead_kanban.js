var HideStageLeadKanban = BX.namespace('hide_stage_lead_kanban');

HideStageLeadKanban = {
    init: function() {
        BX.addCustomEvent('Kanban.Grid:onRender', BX.delegate(this.kanbanHandler, this));
    },
    kanbanHandler: function(grid){
        var node;
		var kanban = this;
        if(grid.hasOwnProperty('columns')) {
			BX.ajax.get('/local/php_interface/handler/hide_stage_lead_kanban_ajax.php', {
				sessid : BX.bitrix_sessid()
			}, function(response) {
				response = JSON.parse(response);
				if(response.length > 0){
					for(var i in grid.columns) {
						if(kanban.inArray(i, response)){
							node = grid['columns'][i]['layout']['container']; 
							kanban.hideColumn(node);
						}
					}
				}
			});
        }
    },
    hideColumn: function(node) {
        if (node != null) {
            BX.remove(node);
        }
    },
	inArray: function(value, array) {
		var length = array.length;
		for(var i = 0; i < length; i++) {
			if(array[i] == value) return true;
		}
		return false;
	}
}

HideStageLeadKanban.init();