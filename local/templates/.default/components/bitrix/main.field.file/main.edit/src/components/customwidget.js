
import { defineComponent, ref, watch } from "ui.vue3";
 
const cssUrl = (u) => (u ? `url("${u}")` : "");
const getFileIcon = (ext) => {
  const m = {
    pdf: "📄",
    doc: "📝",
    docx: "📝",
    xls: "📊",
    xlsx: "📊",
    ppt: "📊",
    pptx: "📊",
    jpg: "🖼️",
    jpeg: "🖼️",
    png: "🖼️",
    gif: "🖼️",
    svg: "🖼️",
    webp: "🖼️",
    mp4: "🎥",
    mp3: "🎵",
    zip: "🗜️",
    rar: "🗜️",
    txt: "📄",
  };
  return m[(ext || "").toLowerCase()] || "📁";
};

export const CustomWidget = defineComponent({
  name: "CustomWidget",
  props: {
    files: { type: Array, default: () => [] },
    readonly: { type: Boolean, default: false },
  },

  // ... existing code ...
  emits: ["reorder", "remove", "upload"],
  setup(props, { emit }) {
    console.log('CustomWidget initial props.files:', JSON.parse(JSON.stringify(props.files)));

    watch(() => props.files, (newVal) => {
      console.log('CustomWidget props.files updated:', JSON.parse(JSON.stringify(newVal)));
    }, { deep: true });

    const draggedIndex = ref(null);

    const onDragStart = (e, i) => {
      if (props.readonly) return;
      draggedIndex.value = i;
      e.dataTransfer.effectAllowed = "move";
      e.dataTransfer.setData("text/plain", String(i));
    };
    const onDragOver = (e) => {
      if (!props.readonly) e.preventDefault();
    };
    const onDrop = (e, i) => {
      if (props.readonly) return;
      e.preventDefault();
      const from = Number(e.dataTransfer.getData("text/plain"));
      if (Number.isNaN(from) || from === i) return;
      const list = props.files.slice();
      const [moved] = list.splice(from, 1);
      list.splice(i, 0, moved);
      emit("reorder", list);
      draggedIndex.value = null;
    };

    const removeFile = (id) => {
      if (!props.readonly) emit("remove", id);
    };

    const handleFileUpload = (e) => {
      if (props.readonly) return;
      const files = e.target.files;
      if (files && files.length) emit("upload", files);
      e.target.value = "";
    };
    const handleDropUpload = (e) => {
      if (props.readonly) return;
      e.preventDefault();
      const files = e.dataTransfer.files;
      if (files && files.length) emit("upload", files);
    };

    return {
      onDragStart,
      onDragOver,
      onDrop,
      removeFile,
      handleFileUpload,
      handleDropUpload,
      cssUrl,
      getFileIcon,
    };
  },

  template: `
    <div class="custom-tile-widget">
      <div v-for="(file, i) in files"
           :key="(file.id || file.signedFileId || '') + ':' + i"
           class="tile-item"
           :class="{ 'uploading': file.uploadStatus === 'uploading' }"
           :draggable="!readonly && file.uploadStatus !== 'uploading'"
           @dragstart="onDragStart($event, i)"
           @dragover="onDragOver"
           @drop="onDrop($event, i)">

        <button v-if="!readonly && file.uploadStatus !== 'uploading'"
                class="tile-remove" @click="removeFile(file.id)" title="Remove">×</button>

        <div class="tile-preview">
          <div v-if="file.src && file.isImage"
               class="tile-image"
               :style="{ 'backgroundImage': cssUrl(file.src) }"
               :title="file.name"></div>

          <div v-else class="tile-icon">
            <div class="file-type-icon">{{ getFileIcon(file.extension) }}</div>
            <span v-if="file.extension" class="file-ext">{{ file.extension.toUpperCase() }}</span>
          </div>
        </div>

        <div class="tile-info">
          <div class="tile-name" :title="file.name">{{ file.name }}</div>

          <div v-if="file.uploadStatus === 'uploading'" class="upload-progress">
            <div class="progress-bar" :style="{ width: (file.progress || 0) + '%' }"></div>
            <div class="progress-text">{{ (file.progress || 0) }}%</div>
          </div>
        </div>
      </div>

      <div class="upload-zone" v-if="!readonly">
        <input type="file" multiple @change="handleFileUpload" style="display:none" ref="fileInput" />
        <div class="drop-area"
             @click="$refs.fileInput.click()"
             @dragover.prevent @dragenter.prevent
             @drop.prevent="handleDropUpload">
          <div class="drop-content">
            <div class="drop-icon">📁</div>
            <p class="drop-text">Drop files here<br>or click to upload</p>
          </div>
        </div>
      </div>
    </div>
  `,
});
