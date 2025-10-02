import { defineComponent, ref } from "ui.vue3";

export const CustomTileWidget = defineComponent({
  name: "CustomTileWidget",
  props: {
    files: { type: Array, default: () => [] },
    readonly: { type: Boolean, default: false },
  },
  emits: ["reorder", "remove", "upload"],
  setup(props, { emit }) {
    const draggedIndex = ref(null);

    const onDragStart = (e, i) => {
      if (props.readonly) return;
      draggedIndex.value = i;
      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = "move";
        e.dataTransfer.setData("text/plain", String(i));
      }
    };

    const onDragOver = (e) => {
      if (props.readonly) return;
      e.preventDefault();
      if (e.dataTransfer) e.dataTransfer.dropEffect = "move";
    };

    const onDrop = (e, i) => {
      if (props.readonly) return;
      e.preventDefault();
      const from = Number(e.dataTransfer?.getData("text/plain"));
      if (Number.isNaN(from) || from === i) {
        draggedIndex.value = null;
        return;
      }
      const list = props.files.slice();
      const [moved] = list.splice(from, 1);
      list.splice(i, 0, moved);
      emit("reorder", list);
      draggedIndex.value = null;
    };

    const onDragEnd = () => {
      draggedIndex.value = null;
    };

    const cssUrl = (u) => (u ? `url("${u}")` : "");

    const getFileIcon = (ext) => {
      const map = {
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
      return map[(ext || "").toLowerCase()] || "📁";
    };

    const removeFile = (id) => {
      if (props.readonly) return;
      emit("remove", id);
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
      draggedIndex,
      onDragStart,
      onDragOver,
      onDrop,
      onDragEnd,
      cssUrl,
      getFileIcon,
      removeFile,
      handleFileUpload,
      handleDropUpload,
    };
  },
  template: `
    <div class="custom-tile-widget">
      <div v-for="(file, i) in files"
           :key="String(file.signedFileId || file.id || i)"
           class="tile-item"
           :class="{
             'dragging': draggedIndex === i,
             'uploading': file.uploadStatus === 'uploading',
             'upload-failed': file.uploadStatus === 'failed'
           }"
           :draggable="!readonly && file.uploadStatus !== 'uploading'"
           @dragstart="onDragStart($event, i)"
           @dragover="onDragOver"
           @drop="onDrop($event, i)"
           @dragend="onDragEnd">

        <div class="tile-content">
          <button
            v-if="!readonly && file.uploadStatus !== 'uploading'"
            type="button"
            class="tile-remove"
            @click="removeFile(file.id)"
            title="Remove file">×</button>

          <div v-if="file.uploadStatus === 'uploading'" class="upload-progress">
            <div class="progress-bar" :style="{ width: (parseInt(file.progress || 0, 10)) + '%' }"></div>
            <div class="progress-text">{{ parseInt(file.progress || 0, 10) }}%</div>
          </div>

          <div class="tile-preview">
            <div v-if="file.src && file.isImage" class="tile-image" :style="{ backgroundImage: cssUrl(file.src) }" :title="file.name"></div>
            
            <div v-else class="tile-icon">
              <div class="file-type-icon">{{ getFileIcon(file.extension) }}</div>
              <span v-if="file.extension" class="file-ext">{{ String(file.extension).toUpperCase() }}</span>
            </div>
          </div>

          <div class="tile-info">
            <div class="tile-name" :title="file.name">{{ file.name }}</div>
          </div>
        </div>
      </div>

      <div class="upload-zone" v-if="!readonly">
        <input type="file" multiple @change="handleFileUpload" style="display:none" ref="fileInput" accept="*/*" />
        <div class="drop-area"
             @click="$refs.fileInput.click()"
             @dragover.prevent
             @dragenter.prevent
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
