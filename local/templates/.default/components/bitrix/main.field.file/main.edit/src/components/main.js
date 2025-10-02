// main.js
import { Type } from "main.core";
import { defineComponent, ref, computed, onMounted, nextTick } from "ui.vue3";
import { TileWidgetComponent } from "ui.uploader.tile-widget";
import { InputManager } from "./inputmanager";

/* ---------------- helpers ---------------- */
const toExt = (n) => (n ? String(n).split(".").pop().toLowerCase() : "");
const tokenOf = (f) => (f && (f.signedFileId || f.id)) || "";

/* ---------------- Custom Tile (drag/drop + upload) ---------------- */
const CustomTileWidget = {
  name: "CustomTileWidget",
  props: {
    files: { type: Array, default: () => [] },
    readonly: { type: Boolean, default: false },
  },
  emits: ["reorder", "remove", "upload"],
  setup(props, { emit }) {
    const draggedIndex = ref(null);
    const dragOverIndex = ref(null);
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

    const handleDragStart = (e, i) => {
      if (props.readonly) return;
      draggedIndex.value = i;
      e.dataTransfer.effectAllowed = "move";
      e.dataTransfer.setData("text/plain", String(i));
      e.currentTarget.style.opacity = "0.5";
    };
    const handleDragOver = (e, i) => {
      if (props.readonly) return;
      e.preventDefault();
      dragOverIndex.value = i;
      e.dataTransfer.dropEffect = "move";
    };
    const handleDrop = (e, i) => {
      if (props.readonly) return;
      e.preventDefault();
      const from = draggedIndex.value;
      if (from === null || from === i) return reset();
      const list = props.files.slice();
      const [moved] = list.splice(from, 1);
      list.splice(i, 0, moved);
      emit("reorder", list);
      reset();
    };
    const handleDragEnd = (e) => {
      e.currentTarget.style.opacity = "1";
      reset();
    };
    const reset = () => {
      draggedIndex.value = null;
      dragOverIndex.value = null;
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
      draggedIndex,
      dragOverIndex,
      cssUrl,
      getFileIcon,
      handleDragStart,
      handleDragOver,
      handleDrop,
      handleDragEnd,
      removeFile,
      handleFileUpload,
      handleDropUpload,
    };
  },
  template: `
    <div class="custom-tile-widget">
    <div class="custom-container">
      <div v-for="(file, index) in files"
           :key="(file.signedFileId || file.id || index) + ':' + index"
           class="tile-item"
           :class="{
             'drag-over': dragOverIndex===index,
             'dragging': draggedIndex===index,
             'uploading': file.uploadStatus==='uploading',
             'upload-failed': file.uploadStatus==='failed'
           }"
           :draggable="!readonly && file.uploadStatus!=='uploading'"
           @dragstart="handleDragStart($event, index)"
           @dragover="handleDragOver($event, index)"
           @drop="handleDrop($event, index)"
           @dragend="handleDragEnd">
        <div class="tile-content">
          <button v-if="!readonly && file.uploadStatus!=='uploading'"
                  class="tile-remove" @click="removeFile(file.id)" title="Remove file">×</button>

          <div v-if="file.uploadStatus==='uploading'" class="upload-progress">
            <div class="progress-bar" :style="{ width: (file.progress||0) + '%' }"></div>
            <div class="progress-text">{{ file.progress || 0 }}%</div>
          </div>

          <div class="tile-preview">
            <div v-if="file.src && file.isImage"
                 class="tile-image"
                 :style="{ backgroundImage: cssUrl(file.src) }"
                 :title="file.name"></div>
            <div v-else class="tile-icon">
              <div class="file-type-icon">{{ getFileIcon(file.extension) }}</div>
              <span v-if="file.extension" class="file-ext">{{ file.extension.toUpperCase() }}</span>
            </div>
          </div>

          <div class="tile-info">
            <div class="tile-name" :title="file.name">{{ file.name }}</div>
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
    </div>
  `,
};

/* ---------------- Main ---------------- */
export const Main = defineComponent({
  name: "Main",
  components: { InputManager, TileWidgetComponent, CustomTileWidget },
  props: {
    controlId: { type: String, required: true },
    context: { type: Object, required: true }, // { entityId, fieldName, multiple, readonly }
    filledValues: { type: Array, default: () => [] }, // numeric ids OR signed ids
    fileDetails: { type: Array, default: () => [] }, // [{id,name,src,extension,signedFileId,...}]
  },
  setup(props) {
    const uploaderRef = ref(null);
    const inputMgrRef = ref(null);
    const fileData = ref([]);
    const errorMessage = ref("");

    const MAX_FILES = 20;
    const fileTokens = ref([...(props.filledValues || [])]);

    const pushTokensToInput = () => {
      const tokens = [];
      const seen = new Set();
      for (const f of fileData.value) {
        if (f.uploadStatus !== "completed") continue;
        const t = tokenOf(f);
        if (!t) continue;
        const k = String(t);
        if (!seen.has(k)) {
          seen.add(k);
          tokens.push(t);
        }
      }
      console.log("[pushTokensToInput] tokens →", tokens);
      fileTokens.value = tokens;
      inputMgrRef.value?.setValues(tokens);
    };

    /** Load BX files' metadata first, then build tiles */
    const syncFromBx = async () => {
      const bx = uploaderRef.value?.uploader;
      if (!bx) {
        console.log("[syncFromBx] BX uploader not ready yet");
        return;
      }
      const bxFiles = bx.getFiles?.() || [];
      console.log("[syncFromBx] bx.getFiles()", bxFiles.length, bxFiles);

      // 1) Ensure metadata is fetched
      const loaders = [];
      bxFiles.forEach((file, i) => {
        const hasLoad = typeof file?.load === "function";
        console.log(
          `[syncFromBx] #${i} isComplete=${file?.isComplete?.()} hasLoad=${hasLoad}`,
        );
        if (hasLoad) {
          try {
            const p = file.load().catch((e) => {
              console.warn(`[syncFromBx] load() failed for #${i}`, e);
            });
            loaders.push(p);
          } catch (e) {
            console.warn(`[syncFromBx] load() threw for #${i}`, e);
          }
        }
      });
      if (loaders.length) {
        console.log(`[syncFromBx] awaiting ${loaders.length} load() calls...`);
        await Promise.allSettled(loaders);
      }

      // 2) Build completed tiles (name/preview should be available now)
      const completed = [];
      const seenTok = new Set();

      bxFiles.forEach((file, idx) => {
        // after load() Bitrix often marks as "server" (not strictly "complete"),
        // so rely on having IDs + name instead of isComplete only
        const real = file.getCustomData?.()?.realFileId;
        const id = Number.isFinite(real) ? real : file.getServerFileId?.();
        const name = file.getName?.() || `File ${id}`;
        const ext = toExt(name);
        const previewUrl = file.getPreviewUrl?.() || "";
        const downloadUrl = file.getDownloadUrl?.() || "";
        const signed = file.getSignedFileId?.() || null;
        const token = signed || id;

        const isImageExt = [
          "jpg",
          "jpeg",
          "png",
          "gif",
          "webp",
          "svg",
          "bmp",
        ].includes(ext);
        let finalSrc = previewUrl || (isImageExt ? downloadUrl : "");

        console.log("[syncFromBx] resolved", {
          idx,
          id,
          name,
          ext,
          signed,
          previewUrl,
          downloadUrl,
          finalSrc,
          hasPreviewMethod: !!file.getPreviewUrl,
          hasDownloadMethod: !!file.getDownloadUrl,
        });

        if (token == null || seenTok.has(String(token))) return;
        seenTok.add(String(token));

        // Check if this file is already in fileData as an uploading tile
        const existingIndex = fileData.value.findIndex(f => 
          String(f.id) === String(id) || 
          (f.uploadStatus === "uploading" && f.name === name)
        );

        const fileEntry = {
          id,
          name,
          src: finalSrc || "",
          isImage: isImageExt && !!finalSrc,
          extension: ext,
          uploadStatus: "completed",
          progress: 100,
          signedFileId: signed || null,
        };

        if (existingIndex > -1) {
          // Update existing uploading tile to completed
          console.log("[syncFromBx] updating existing tile", { existingIndex, name, id });
          fileData.value[existingIndex] = fileEntry;
        } else {
          // Add as new completed tile
          completed.push(fileEntry);
        }
      });

      // keep uploading tiles that weren't matched/updated
      const inflight = fileData.value.filter(
        (f) =>
          f.uploadStatus !== "completed" && !seenTok.has(String(tokenOf(f))),
      );

      // Only add new completed tiles (not the updated ones which are already in fileData)
      fileData.value = [...fileData.value.filter(f => f.uploadStatus === "completed"), ...completed, ...inflight];
      console.log("[syncFromBx] fileData →", fileData.value);
      pushTokensToInput();

      // Check if we have files but no proper previews/names - retry after delay
      const hasFilesWithoutProperData = completed.some(f => 
        (!f.src && f.isImage) || f.name.startsWith('File ')
      );
      
      if (hasFilesWithoutProperData && !window._syncRetryInProgress) {
        console.log("[syncFromBx] Detected files without proper previews, scheduling retry...");
        window._syncRetryInProgress = true;
        setTimeout(async () => {
          console.log("[syncFromBx] Retry attempt - reloading file metadata...");
          await syncFromBx();
          window._syncRetryInProgress = false;
        }, 1000); // Wait 1 second for Bitrix to fully initialize
      }
    };    const uploaderOptions = computed(() => ({
      controller: "main.fileUploader.fieldFileUploaderController",
  // Pass full context to let Bitrix controller hydrate existing tokens
  controllerOptions: props.context,
      files: fileTokens.value,
      multiple: !!props.context?.multiple,
      autoUpload: true,
      treatOversizeImageAsFile: true,
      events: {
        "File:onAdd": (ev) => {
          const bxFile = ev?.getData?.()?.file;
          const name = bxFile?.getName?.();
          if (!bxFile || !name) return;
          for (let i = fileData.value.length - 1; i >= 0; i--) {
            const f = fileData.value[i];
            if (f.uploadStatus === "uploading" && f.name === name) {
              const newId = bxFile.getId?.() || bxFile.getServerFileId?.();
              f.id = newId;
              f.progress = 0;
              console.log("[onAdd] temp→BX id", { name, newId });
              break;
            }
          }
        },
        "File:onProgress": (ev) => {
          const file = ev?.getData?.()?.file;
          if (!file) return;
          const id = file.getId?.() || file.getServerFileId?.();
          const idx = fileData.value.findIndex(
            (x) => String(x.id) === String(id),
          );
          if (idx > -1)
            fileData.value[idx].progress = file.getProgress?.() || 0;
        },
        "File:onUploadComplete": () => {
          console.log("[onUploadComplete]");
          setTimeout(syncFromBx, 0);
        },
        onUploadComplete: () => {
          console.log("[onUploadComplete (alt)]");
          setTimeout(syncFromBx, 0);
        },
        "File:onRemove": (ev) => {
          const file = ev?.getData?.()?.file;
          if (!file) return;
          const serverId = file.getServerFileId?.();
          const realId = file.getCustomData?.()?.realFileId;
          const rmToken = String(Number.isFinite(realId) ? realId : serverId);
          console.log("[onRemove] token", rmToken);
          inputMgrRef.value?.addDeleted(rmToken);
          fileData.value = fileData.value.filter(
            (f) =>
              String(tokenOf(f)) !== rmToken &&
              String(f.id) !== String(file.getId?.()),
          );
          pushTokensToInput();
        },
      },
    }));

    /* ---------------- UI handlers ---------------- */
    const handleUpload = (fileList) => {
      const bx = uploaderRef.value?.uploader;
      if (!bx || !fileList) return;

      // Clear previous error messages
      errorMessage.value = "";

      const filesToUpload = [...fileList];
      const currentFileCount = fileData.value.length;
      const totalAfterUpload = currentFileCount + filesToUpload.length;

      // Check if adding these files would exceed the limit
      if (totalAfterUpload > MAX_FILES) {
        const allowed = Math.max(0, MAX_FILES - currentFileCount);
        errorMessage.value = `Maximum ${MAX_FILES} files allowed. You can only upload ${allowed} more file(s).`;
        console.warn("[handleUpload] File limit exceeded", {
          current: currentFileCount,
          trying: filesToUpload.length,
          max: MAX_FILES,
          allowed
        });
        
        // Clear the error message after 5 seconds
        setTimeout(() => {
          errorMessage.value = "";
        }, 5000);
        
        return;
      }

      filesToUpload.forEach((f) => {
        const tmpId = `tmp_${Date.now()}_${Math.random()
          .toString(36)
          .slice(2)}`;
        const ext = toExt(f.name);
        const isImage =
          f.type?.startsWith("image/") ||
          ["jpg", "jpeg", "png", "gif", "webp", "svg", "bmp"].includes(ext);

        let previewSrc = "";
        if (isImage && f.type?.startsWith("image/")) {
          try {
            previewSrc = URL.createObjectURL(f);
          } catch {}
        }

        console.log("[handleUpload] temp tile", {
          name: f.name,
          ext,
          isImage,
          previewSrc,
        });

        fileData.value.push({
          id: tmpId,
          name: f.name,
          size: f.size,
          src: previewSrc,
          isImage,
          extension: ext,
          uploadStatus: "uploading",
          progress: 0,
        });
        bx.addFile(f);
      });
    };

    const handleReorder = (reordered) => {
      const seen = new Set();
      const completed = [];
      const inflight = [];
      for (const f of reordered) {
        if (f.uploadStatus !== "completed") {
          inflight.push(f);
          continue;
        }
        const t = tokenOf(f);
        if (!t) continue;
        const k = String(t);
        if (!seen.has(k)) {
          seen.add(k);
          completed.push(f);
        }
      }
      fileData.value = [
        ...completed.map((f) => ({ ...f })),
        ...inflight.map((f) => ({ ...f })),
      ];
      nextTick(pushTokensToInput);
      nextTick(() => inputMgrRef.value?.updateOrder(fileTokens.value));
    };

    const handleRemove = (fileId) => {
      const bx = uploaderRef.value?.uploader;
      if (bx) {
        const f = bx
          .getFiles()
          .find(
            (ff) =>
              String(ff.getServerFileId?.()) === String(fileId) ||
              String(ff.getId?.()) === String(fileId),
          );
        if (f) bx.removeFile(f);
      }
      const removed = fileData.value.find(
        (f) => String(f.id) === String(fileId),
      );
      fileData.value = fileData.value.filter(
        (f) => String(f.id) !== String(fileId),
      );
      inputMgrRef.value?.addDeleted(
        removed ? removed.signedFileId || removed.id : fileId,
      );
      pushTokensToInput();
    };

    /* ---------------- lifecycle ---------------- */
    onMounted(() => {
      if (Type.isArrayFilled(props.fileDetails)) {
        fileData.value = props.fileDetails.map((f) => ({
          id: f.id,
          name: f.name,
          src: f.src || "",
          isImage: !!f.src,
          extension: toExt(f.name || ""),
          uploadStatus: "completed",
          progress: 100,
          signedFileId: f.signedFileId || null,
        }));
        console.log("[onMounted] boot from fileDetails", fileData.value);
        pushTokensToInput();
      } else {
        console.log("[onMounted] No fileDetails provided, will use Bitrix sync");
      }

      nextTick(() => {
        console.log(
          "[onMounted] seed InputManager with tokens",
          fileTokens.value,
        );
        inputMgrRef.value?.setValues(fileTokens.value);
      });

      // Poll BX a bit, then force a metadata load+sync
      let tries = 0;
      const maxTries = 40; // ~4s
      const poll = async () => {
        const bx = uploaderRef.value?.uploader;
        const list = bx?.getFiles?.() || [];
        console.log(
          `[poll] bx_ready=${!!bx} files=${list.length} try=${tries}`,
        );

        // TEMPORARILY DISABLED - Skip Bitrix sync to test our mock data
        // if (fileData.value.length > 0) {
        //   console.log("[poll] Using test data, skipping BX sync");
        //   return;
        // }

        if (!bx || (props.filledValues?.length && list.length === 0)) {
          if (tries++ < maxTries) return void setTimeout(poll, 100);
        }

        await syncFromBx();

        if (
          fileData.value.length === 0 &&
          props.filledValues?.length &&
          tries < maxTries
        ) {
          return void setTimeout(poll, 200);
        }
      };
      setTimeout(poll, 0);

      const valueChanger = inputMgrRef.value?.$refs.valueChanger;
      const form = valueChanger?.closest?.("form");
      if (form) {
        form.addEventListener(
          "submit",
          (e) => {
            const pending = fileData.value.some(
              (f) => f.uploadStatus === "uploading",
            );
            if (pending) {
              console.warn("[submit] blocked: uploads pending");
              e.preventDefault();
              return;
            }
            console.log("[submit] commit tokens");
            pushTokensToInput();
          },
          { capture: true },
        );
      }
    });

    return {
      uploaderRef,
      inputMgrRef,
      fileData,
      fileTokens,
      uploaderOptions,
      handleUpload,
      handleReorder,
      handleRemove,
      context: props.context,
      errorMessage,
    };
  },
  template: `
    <div class="main-field-file-wrapper">
      <InputManager
        ref="inputMgrRef"
        :controlId="controlId"
        :controlName="context.fieldName"
        :multiple="context.multiple"
        :filledValues="filledValues"
      />

      <!-- Hidden Bitrix uploader (engine only) -->
      <TileWidgetComponent
        ref="uploaderRef"
        :uploaderOptions="uploaderOptions"
        style="display:none"
      />

      <!-- Visible custom tiles -->
      <CustomTileWidget
        :files="fileData"
        :readonly="context.readonly || false"
        @upload="handleUpload"
        @reorder="handleReorder"
        @remove="handleRemove"
      />

      <!-- Error message display -->
      <div v-if="errorMessage" class="file-upload-error" style="color: #d32f2f; background: #ffebee; padding: 8px 12px; border-radius: 4px; margin-top: 8px; border-left: 3px solid #d32f2f;">
        {{ errorMessage }}
      </div>

      <div v-if="!fileData.length" class="no-files-message">No files uploaded yet.</div>
    </div>
  `,
});

export { Main as default };
