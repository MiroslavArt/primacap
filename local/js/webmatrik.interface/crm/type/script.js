BX.namespace('Webmatrik.Interface.Crm.Type');

BX.Webmatrik.Interface.Crm.Type = {
    init: function () {
        console.log('init type');
        //BX.addCustomEvent('BX.Crm.EntityEditor:onControlModeChange', BX.delegate(this.changeControlWidth, this));
        //BX.addCustomEvent('BX.UI.EntityConfigurationManager:onInitialize', BX.delegate(this.fixTitleWidth, this));
        BX.addCustomEvent('BX.Crm.EntityEditorSection:onLayout', BX.delegate(this.fixTitleWidth, this));
        BX.addCustomEvent('BX.UI.EntityEditorField:onLayout', BX.delegate(this.fixDescWidth, this));
    },
    changeControlWidth: function(event, data) {
        //console.log(event)
        //console.log(data)
		if(event._id == 'DYNAMIC_1036_details_C8_editor') {
			$(document).ready(function() {
				console.log('match');
				console.log($('.ui-selector-dialog')); 
            	$('.ui-selector-dialog').first().css('width', '800px');  
			});

        }
    }, 
    fixTitleWidth: function(event, data) {
        if(event._id == 'main')	{
            console.log('here')
            console.log($('#title_text'))   
            //console.log($(event._input))
            $($('#title_text')).on('blur', function() {
                const inputValue = $(this).val();
                
                if (inputValue.length > 50) {
                    // Показываем alert с сообщением об ошибке
                    
                    BX.UI.Notification.Center.notify({
                            content: ('Title must be below 50 letters')
                    });
                    //alert('Ошибка! Превышено максимальное количество символов (50).');
                    
                    // Возвращаем фокус в поле ввода
                    $(this).focus();
                    
                    // Выделяем лишний текст (опционально)
                    this.setSelectionRange(50, inputValue.length);
                    
                    // Предотвращаем переход (фокус уже возвращен)
                    return false;
                } 
            });
        }
    },
    fixDescWidth: function(event, data) { 
         if(event._id == 'UF_CRM_5_1752508408') {
            console.log('addit')
            console.log($('[name="UF_CRM_5_1752508408"]').first()) 
            $($('[name="UF_CRM_5_1752508408"]').first()).on('blur', function() {
                const inputValue = $(this).val();
                
                if (inputValue.length > 2000) {
                    // Показываем alert с сообщением об ошибке
                    
                    BX.UI.Notification.Center.notify({
                            content: ('Title must be below 2000 letters')
                    });
                    //alert('Ошибка! Превышено максимальное количество символов (50).');
                    
                    // Возвращаем фокус в поле ввода
                    $(this).focus();
                    
                    // Выделяем лишний текст (опционально)
                    this.setSelectionRange(2000, inputValue.length);
                    
                    // Предотвращаем переход (фокус уже возвращен)
                    return false;
                } else if(inputValue.length < 750) {
                    BX.UI.Notification.Center.notify({
                            content: ('Title must be above 750 letters')
                    });
                    //alert('Ошибка! Превышено максимальное количество символов (50).');
                    
                    // Возвращаем фокус в поле ввода
                    $(this).focus();
                    
                    // Выделяем лишний текст (опционально)
                    this.setSelectionRange(0, inputValue.length);
                    
                    // Предотвращаем переход (фокус уже возвращен)
                    return false;
                }
            
            }     
        
            );

        }
    }
}