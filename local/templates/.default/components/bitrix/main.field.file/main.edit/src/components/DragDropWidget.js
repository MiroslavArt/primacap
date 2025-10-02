import { ref, reactive, watch } from 'ui.vue3';

export const DragDropWidget = {
  props: {
    title: {
      type: String,
      default: 'Drag & Drop Items'
    },
    subtitle: {
      type: String,
      default: null
    },
    items: {
      type: Array,
      default: () => []
    },
    layout: {
      type: String,
      default: 'grid',
      validator: value => ['grid', 'list'].includes(value)
    },
    showControls: {
      type: Boolean,
      default: true
    },
    showAddButton: {
      type: Boolean,
      default: true
    },
    editable: {
      type: Boolean,
      default: true
    }
  },
  emits: ['update:items', 'item-action', 'items-reordered'],
  setup(props, { emit }) {
    const localItems = ref([...props.items]);
    const draggedItem = ref(null);
    const draggedIndex = ref(null);
    const showAddForm = ref(false);

    const newItem = reactive({
      title: '',
      image: '',
      description: '',
      tags: []
    });

    watch(() => props.items, (newItems) => {
      localItems.value = [...newItems];
    }, { deep: true });

    watch(localItems, (newItems) => {
      emit('update:items', newItems);
      emit('items-reordered', newItems);
    }, { deep: true });

    const handleDragStart = (item, index) => {
      if (!props.editable) return;
      draggedItem.value = item;
      draggedIndex.value = index;
    };

    const handleDragEnd = () => {
      draggedItem.value = null;
      draggedIndex.value = null;
    };

    const handleDragOver = (event) => {
      event.preventDefault();
    };

    const handleDrop = (targetIndex) => {
      if (!props.editable || draggedIndex.value === null) return;
      event.preventDefault();
      const draggedItemData = localItems.value[draggedIndex.value];
      localItems.value.splice(draggedIndex.value, 1);
      localItems.value.splice(targetIndex, 0, draggedItemData);
      handleDragEnd();
    };

    const moveUp = (index) => {
      if (index > 0) {
        const item = localItems.value[index];
        localItems.value.splice(index, 1);
        localItems.value.splice(index - 1, 0, item);
      }
    };

    const moveDown = (index) => {
      if (index < localItems.value.length - 1) {
        const item = localItems.value[index];
        localItems.value.splice(index, 1);
        localItems.value.splice(index + 1, 0, item);
      }
    };

    const removeItem = (index) => {
      localItems.value.splice(index, 1);
    };

    const addItem = () => {
      if (newItem.title && newItem.image) {
        const item = {
          id: Date.now(),
          title: newItem.title,
          image: newItem.image,
          description: newItem.description,
          tags: newItem.tags
        };
        localItems.value.push(item);
        resetNewItem();
        showAddForm.value = false;
      }
    };

    const cancelAdd = () => {
      resetNewItem();
      showAddForm.value = false;
    };

    const resetNewItem = () => {
      newItem.title = '';
      newItem.image = '';
      newItem.description = '';
      newItem.tags = [];
    };

    const handleItemAction = (item) => {
      emit('item-action', item);
      if (item.button?.action === 'scroll' && item.button?.target) {
        document.querySelector(item.button.target)?.scrollIntoView({
          behavior: 'smooth'
        });
      } else if (item.button?.href) {
        window.open(item.button.href, item.button.target || '_self');
      }
    };

    return {
      localItems,
      draggedItem,
      draggedIndex,
      showAddForm,
      newItem,
      handleDragStart,
      handleDragEnd,
      handleDragOver,
      handleDrop,
      moveUp,
      moveDown,
      removeItem,
      addItem,
      cancelAdd,
      handleItemAction
    };
  },
  template: `
    <div class="drag-drop-widget">
      <div class="widget-header">
        <h3 class="widget-title">{{ title }}</h3>
        <p v-if="subtitle" class="widget-subtitle">{{ subtitle }}</p>
      </div>
      <div class="drag-container" :class="{ 'grid-layout': layout === 'grid', 'list-layout': layout === 'list' }">
        <div v-for="(item, index) in localItems" :key="item.id" class="drag-item" :class="{ 'dragging': draggedItem?.id === item.id }" draggable="true" @dragstart="handleDragStart(item, index)" @dragend="handleDragEnd" @dragover="handleDragOver" @drop="handleDrop(index)">
          <div class="item-image">
            <img :src="item.image" :alt="item.title" />
            <div class="drag-handle">
              <span class="drag-icon">⋮⋮</span>
            </div>
          </div>
          <div class="item-content">
            <h4 class="item-title">{{ item.title }}</h4>
            <p class="item-description">{{ item.description }}</p>
            <div v-if="item.tags" class="item-tags">
              <span v-for="tag in item.tags" :key="tag" class="tag">{{ tag }}</span>
            </div>
            <div class="item-actions">
              <button v-if="item.button" class="item-button" :class="item.button.variant || 'primary'" @click="handleItemAction(item)">{{ item.button.text }}</button>
              <div v-if="showControls" class="item-controls">
                <button @click="moveUp(index)" :disabled="index === 0" class="control-btn">↑</button>
                <button @click="moveDown(index)" :disabled="index === localItems.length - 1" class="control-btn">↓</button>
                <button @click="removeItem(index)" class="control-btn remove">×</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-if="showAddButton" class="add-item-section">
        <button @click="showAddForm = !showAddForm" class="add-button">+ Add New Item</button>
        <div v-if="showAddForm" class="add-form">
          <input v-model="newItem.title" placeholder="Title" class="form-input" />
          <input v-model="newItem.image" placeholder="Image URL" class="form-input" />
          <textarea v-model="newItem.description" placeholder="Description" class="form-textarea"></textarea>
          <div class="form-actions">
            <button @click="addItem" class="btn primary">Add Item</button>
            <button @click="cancelAdd" class="btn secondary">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  `
};
