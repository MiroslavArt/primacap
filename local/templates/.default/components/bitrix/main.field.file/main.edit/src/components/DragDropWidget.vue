<template>
  <div class="drag-drop-widget">
    <div class="widget-header">
      <h3 class="widget-title">{{ title }}</h3>
      <p v-if="subtitle" class="widget-subtitle">{{ subtitle }}</p>
    </div>
    
    <div 
      class="drag-container"
      :class="{ 'grid-layout': layout === 'grid', 'list-layout': layout === 'list' }"
    >
      <div
        v-for="(item, index) in localItems"
        :key="item.id"
        class="drag-item"
        :class="{ 'dragging': draggedItem?.id === item.id }"
        draggable="true"
        @dragstart="handleDragStart(item, index)"
        @dragend="handleDragEnd"
        @dragover="handleDragOver"
        @drop="handleDrop(index)"
      >
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
            <span 
              v-for="tag in item.tags" 
              :key="tag" 
              class="tag"
            >
              {{ tag }}
            </span>
          </div>
          
          <div class="item-actions">
            <button 
              v-if="item.button"
              class="item-button"
              :class="item.button.variant || 'primary'"
              @click="handleItemAction(item)"
            >
              {{ item.button.text }}
            </button>
            
            <div v-if="showControls" class="item-controls">
              <button @click="moveUp(index)" :disabled="index === 0" class="control-btn">
                ↑
              </button>
              <button @click="moveDown(index)" :disabled="index === localItems.length - 1" class="control-btn">
                ↓
              </button>
              <button @click="removeItem(index)" class="control-btn remove">
                ×
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-if="showAddButton" class="add-item-section">
      <button @click="showAddForm = !showAddForm" class="add-button">
        + Add New Item
      </button>
      
      <div v-if="showAddForm" class="add-form">
        <input 
          v-model="newItem.title" 
          placeholder="Title" 
          class="form-input"
        />
        <input 
          v-model="newItem.image" 
          placeholder="Image URL" 
          class="form-input"
        />
        <textarea 
          v-model="newItem.description" 
          placeholder="Description" 
          class="form-textarea"
        ></textarea>
        <div class="form-actions">
          <button @click="addItem" class="btn primary">Add Item</button>
          <button @click="cancelAdd" class="btn secondary">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'

const props = defineProps({
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
})

const emit = defineEmits(['update:items', 'item-action', 'items-reordered'])

const localItems = ref([...props.items])
const draggedItem = ref(null)
const draggedIndex = ref(null)
const showAddForm = ref(false)

const newItem = reactive({
  title: '',
  image: '',
  description: '',
  tags: []
})

// Watch for external items changes
watch(() => props.items, (newItems) => {
  localItems.value = [...newItems]
}, { deep: true })

// Watch for local items changes and emit
watch(localItems, (newItems) => {
  emit('update:items', newItems)
  emit('items-reordered', newItems)
}, { deep: true })

const handleDragStart = (item, index) => {
  if (!props.editable) return
  
  draggedItem.value = item
  draggedIndex.value = index
}

const handleDragEnd = () => {
  draggedItem.value = null
  draggedIndex.value = null
}

const handleDragOver = (event) => {
  event.preventDefault()
}

const handleDrop = (targetIndex) => {
  if (!props.editable || draggedIndex.value === null) return
  
  event.preventDefault()
  
  const draggedItemData = localItems.value[draggedIndex.value]
  localItems.value.splice(draggedIndex.value, 1)
  localItems.value.splice(targetIndex, 0, draggedItemData)
  
  handleDragEnd()
}

const moveUp = (index) => {
  if (index > 0) {
    const item = localItems.value[index]
    localItems.value.splice(index, 1)
    localItems.value.splice(index - 1, 0, item)
  }
}

const moveDown = (index) => {
  if (index < localItems.value.length - 1) {
    const item = localItems.value[index]
    localItems.value.splice(index, 1)
    localItems.value.splice(index + 1, 0, item)
  }
}

const removeItem = (index) => {
  localItems.value.splice(index, 1)
}

const addItem = () => {
  if (newItem.title && newItem.image) {
    const item = {
      id: Date.now(),
      title: newItem.title,
      image: newItem.image,
      description: newItem.description,
      tags: newItem.tags
    }
    
    localItems.value.push(item)
    resetNewItem()
    showAddForm.value = false
  }
}

const cancelAdd = () => {
  resetNewItem()
  showAddForm.value = false
}

const resetNewItem = () => {
  newItem.title = ''
  newItem.image = ''
  newItem.description = ''
  newItem.tags = []
}

const handleItemAction = (item) => {
  emit('item-action', item)
  
  if (item.button?.action === 'scroll' && item.button?.target) {
    document.querySelector(item.button.target)?.scrollIntoView({ 
      behavior: 'smooth' 
    })
  } else if (item.button?.href) {
    window.open(item.button.href, item.button.target || '_self')
  }
}
</script>

<style scoped>
.drag-drop-widget {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.widget-header {
  text-align: center;
  margin-bottom: 3rem;
}

.widget-title {
  font-size: 2.5rem;
  font-weight: bold;
  margin-bottom: 1rem;
  color: #333;
}

.widget-subtitle {
  font-size: 1.1rem;
  color: #666;
  max-width: 600px;
  margin: 0 auto;
}

.drag-container.grid-layout {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.drag-container.list-layout {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.drag-item {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: all 0.3s ease;
  cursor: grab;
  position: relative;
}

.drag-item:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.drag-item.dragging {
  opacity: 0.5;
  transform: rotate(5deg);
  cursor: grabbing;
}

.list-layout .drag-item {
  display: flex;
  flex-direction: row;
}

.item-image {
  position: relative;
  overflow: hidden;
}

.grid-layout .item-image {
  height: 200px;
}

.list-layout .item-image {
  width: 200px;
  min-width: 200px;
  height: 150px;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.drag-item:hover .item-image img {
  transform: scale(1.05);
}

.drag-handle {
  position: absolute;
  top: 10px;
  right: 10px;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  padding: 5px;
  border-radius: 4px;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.drag-item:hover .drag-handle {
  opacity: 1;
}

.drag-icon {
  font-size: 12px;
  line-height: 1;
}

.item-content {
  padding: 1.5rem;
  flex: 1;
}

.item-title {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #333;
}

.item-description {
  color: #666;
  line-height: 1.6;
  margin-bottom: 1rem;
}

.item-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.tag {
  background: #f0f0f0;
  padding: 0.25rem 0.75rem;
  border-radius: 15px;
  font-size: 0.85rem;
  color: #666;
}

.item-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.item-button {
  background: #007bff;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.3s ease;
}

.item-button:hover {
  background: #0056b3;
}

.item-button.secondary {
  background: #6c757d;
}

.item-button.secondary:hover {
  background: #545b62;
}

.item-controls {
  display: flex;
  gap: 0.5rem;
}

.control-btn {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
}

.control-btn:hover {
  background: #e9ecef;
}

.control-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.control-btn.remove {
  background: #dc3545;
  color: white;
  border-color: #dc3545;
}

.control-btn.remove:hover {
  background: #c82333;
}

.add-item-section {
  margin-top: 2rem;
  text-align: center;
}

.add-button {
  background: #28a745;
  color: white;
  border: none;
  padding: 1rem 2rem;
  border-radius: 8px;
  font-size: 1.1rem;
  cursor: pointer;
  transition: background 0.3s ease;
}

.add-button:hover {
  background: #218838;
}

.add-form {
  max-width: 500px;
  margin: 1rem auto;
  padding: 2rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.form-input,
.form-textarea {
  width: 100%;
  padding: 0.75rem;
  margin-bottom: 1rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
}

.form-textarea {
  min-height: 100px;
  resize: vertical;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}

.btn.primary {
  background: #007bff;
  color: white;
}

.btn.secondary {
  background: #6c757d;
  color: white;
}

@media (max-width: 768px) {
  .drag-container.grid-layout {
    grid-template-columns: 1fr;
  }
  
  .list-layout .drag-item {
    flex-direction: column;
  }
  
  .list-layout .item-image {
    width: 100%;
    height: 200px;
  }
  
  .item-actions {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .item-controls {
    justify-content: center;
  }
}
</style>
