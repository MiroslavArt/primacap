/* eslint-disable */
this.BX = this.BX || {};
this.BX.Main = this.BX.Main || {};
this.BX.Main.Field = this.BX.Main.Field || {};
(function (exports,main_core,ui_vue3,ui_uploader_tileWidget) {
  'use strict';

  // inputmanager.js
  var InputManager = {
    name: "InputManager",
    props: {
      controlId: {
        type: String,
        required: true
      },
      controlName: {
        type: String,
        required: true
      },
      // e.g. "UF_CRM_XXXXX"
      multiple: {
        type: Boolean,
        required: true
      },
      filledValues: {
        type: Array,
        "default": function _default() {
          return [];
        }
      } // initial tokens
    },
    data: function data() {
      return {
        // keep as strings to avoid 291 === "291" mismatches
        values: Array.isArray(this.filledValues) ? this.filledValues.map(String) : [],
        deletedValues: []
      };
    },
    methods: {
      fireChange: function fireChange() {
        // notify Bitrix editor that hidden inputs changed
        BX.fireEvent(this.$refs.valueChanger, "change");
      },
      // Replace the token list (already ordered & deduped by caller)
      setValues: function setValues(newValues) {
        var next = Array.from(newValues || []).map(String);
        var prev = this.values;
        this.values = next;
        if (!this.arraysAreEqual(prev, this.values)) this.fireChange();
      },
      // Reorder-only variant (same semantics as setValues)
      updateOrder: function updateOrder(newOrder) {
        var next = Array.from(newOrder || []).map(String);
        var prev = this.values;
        this.values = next;
        if (!this.arraysAreEqual(prev, this.values)) this.fireChange();
      },
      addDeleted: function addDeleted(fileToken) {
        var t = String(fileToken);
        this.deletedValues = [].concat(babelHelpers.toConsumableArray(this.deletedValues), [t]);
        this.fireChange();
      },
      removeValue: function removeValue(fileToken) {
        var t = String(fileToken);
        var idx = this.values.indexOf(t);
        if (idx > -1) {
          this.values.splice(idx, 1);
          this.fireChange();
        }
      },
      arraysAreEqual: function arraysAreEqual(a, b) {
        if (!Array.isArray(a) || !Array.isArray(b)) return false;
        if (a.length !== b.length) return false;
        for (var i = 0; i < a.length; i++) {
          if (a[i] !== b[i]) return false;
        }
        return true;
      }
    },
    template: "\n    <input ref=\"valueChanger\" type=\"hidden\" />\n    <div class=\"uf-hidden-inputs\" style=\"display: none;\">\n      <!-- tokens -->\n      <div v-if=\"values.length\">\n        <input\n          v-if=\"multiple\"\n          v-for=\"(val, i) in values\"\n          :key=\"'v-'+i\"\n          type=\"hidden\"\n          :name=\"controlName + '[]'\"\n          :value=\"val\"\n        />\n        <input\n          v-else\n          type=\"hidden\"\n          :name=\"controlName\"\n          :value=\"values[values.length - 1]\"\n        />\n      </div>\n      <div v-else>\n        <input type=\"hidden\" :name=\"multiple ? controlName + '[]' : controlName\" />\n      </div>\n\n      <!-- deletions -->\n      <div v-if=\"deletedValues.length\">\n        <input\n          v-if=\"multiple\"\n          v-for=\"(val, i) in deletedValues\"\n          :key=\"'d1-'+i\"\n          type=\"hidden\"\n          :name=\"controlName + '_del[]'\"\n          :value=\"val\"\n        />\n        <input\n          v-else\n          type=\"hidden\"\n          :name=\"controlName + '_del'\"\n          :value=\"deletedValues[0]\"\n        />\n        <input\n          v-for=\"(val, i) in deletedValues\"\n          :key=\"'d2-'+i\"\n          type=\"hidden\"\n          :name=\"controlId + '_deleted[]'\"\n          :value=\"val\"\n        />\n      </div>\n    </div>\n  "
  };

  function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
  function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
  function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
  function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
  function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
  function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }

  /* ---------------- helpers ---------------- */
  var toExt = function toExt(n) {
    return n ? String(n).split(".").pop().toLowerCase() : "";
  };
  var tokenOf = function tokenOf(f) {
    return f && (f.signedFileId || f.id) || "";
  };

  /* ---------------- Custom Tile (drag/drop + upload) ---------------- */
  var CustomTileWidget = {
    name: "CustomTileWidget",
    props: {
      files: {
        type: Array,
        "default": function _default() {
          return [];
        }
      },
      readonly: {
        type: Boolean,
        "default": false
      }
    },
    emits: ["reorder", "remove", "upload"],
    setup: function setup(props, _ref) {
      var emit = _ref.emit;
      var draggedIndex = ui_vue3.ref(null);
      var dragOverIndex = ui_vue3.ref(null);
      var cssUrl = function cssUrl(u) {
        return u ? "url(\"".concat(u, "\")") : "";
      };
      var getFileIcon = function getFileIcon(ext) {
        var m = {
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
          txt: "📄"
        };
        return m[(ext || "").toLowerCase()] || "📁";
      };
      var handleDragStart = function handleDragStart(e, i) {
        if (props.readonly) return;
        draggedIndex.value = i;
        e.dataTransfer.effectAllowed = "move";
        e.dataTransfer.setData("text/plain", String(i));
        e.currentTarget.style.opacity = "0.5";
      };
      var handleDragOver = function handleDragOver(e, i) {
        if (props.readonly) return;
        e.preventDefault();
        dragOverIndex.value = i;
        e.dataTransfer.dropEffect = "move";
      };
      var handleDrop = function handleDrop(e, i) {
        if (props.readonly) return;
        e.preventDefault();
        var from = draggedIndex.value;
        if (from === null || from === i) return reset();
        var list = props.files.slice();
        var _list$splice = list.splice(from, 1),
          _list$splice2 = babelHelpers.slicedToArray(_list$splice, 1),
          moved = _list$splice2[0];
        list.splice(i, 0, moved);
        emit("reorder", list);
        reset();
      };
      var handleDragEnd = function handleDragEnd(e) {
        e.currentTarget.style.opacity = "1";
        reset();
      };
      var reset = function reset() {
        draggedIndex.value = null;
        dragOverIndex.value = null;
      };
      var removeFile = function removeFile(id) {
        if (!props.readonly) emit("remove", id);
      };
      var handleFileUpload = function handleFileUpload(e) {
        if (props.readonly) return;
        var files = e.target.files;
        if (files && files.length) emit("upload", files);
        e.target.value = "";
      };
      var handleDropUpload = function handleDropUpload(e) {
        if (props.readonly) return;
        e.preventDefault();
        var files = e.dataTransfer.files;
        if (files && files.length) emit("upload", files);
      };
      return {
        draggedIndex: draggedIndex,
        dragOverIndex: dragOverIndex,
        cssUrl: cssUrl,
        getFileIcon: getFileIcon,
        handleDragStart: handleDragStart,
        handleDragOver: handleDragOver,
        handleDrop: handleDrop,
        handleDragEnd: handleDragEnd,
        removeFile: removeFile,
        handleFileUpload: handleFileUpload,
        handleDropUpload: handleDropUpload
      };
    },
    template: "\n    <div class=\"custom-tile-widget\">\n    <div class=\"custom-container\">\n      <div v-for=\"(file, index) in files\"\n           :key=\"(file.signedFileId || file.id || index) + ':' + index\"\n           class=\"tile-item\"\n           :class=\"{\n             'drag-over': dragOverIndex===index,\n             'dragging': draggedIndex===index,\n             'uploading': file.uploadStatus==='uploading',\n             'upload-failed': file.uploadStatus==='failed'\n           }\"\n           :draggable=\"!readonly && file.uploadStatus!=='uploading'\"\n           @dragstart=\"handleDragStart($event, index)\"\n           @dragover=\"handleDragOver($event, index)\"\n           @drop=\"handleDrop($event, index)\"\n           @dragend=\"handleDragEnd\">\n        <div class=\"tile-content\">\n          <button v-if=\"!readonly && file.uploadStatus!=='uploading'\"\n                  class=\"tile-remove\" @click=\"removeFile(file.id)\" title=\"Remove file\">\xD7</button>\n\n          <div v-if=\"file.uploadStatus==='uploading'\" class=\"upload-progress\">\n            <div class=\"progress-bar\" :style=\"{ width: (file.progress||0) + '%' }\"></div>\n            <div class=\"progress-text\">{{ file.progress || 0 }}%</div>\n          </div>\n\n          <div class=\"tile-preview\">\n            <div v-if=\"file.src && file.isImage\"\n                 class=\"tile-image\"\n                 :style=\"{ backgroundImage: cssUrl(file.src) }\"\n                 :title=\"file.name\"></div>\n            <div v-else class=\"tile-icon\">\n              <div class=\"file-type-icon\">{{ getFileIcon(file.extension) }}</div>\n              <span v-if=\"file.extension\" class=\"file-ext\">{{ file.extension.toUpperCase() }}</span>\n            </div>\n          </div>\n\n          <div class=\"tile-info\">\n            <div class=\"tile-name\" :title=\"file.name\">{{ file.name }}</div>\n          </div>\n        </div>\n      </div>\n\n      <div class=\"upload-zone\" v-if=\"!readonly\">\n        <input type=\"file\" multiple @change=\"handleFileUpload\" style=\"display:none\" ref=\"fileInput\" />\n        <div class=\"drop-area\"\n             @click=\"$refs.fileInput.click()\"\n             @dragover.prevent @dragenter.prevent\n             @drop.prevent=\"handleDropUpload\">\n          <div class=\"drop-content\">\n            <div class=\"drop-icon\">\uD83D\uDCC1</div>\n            <p class=\"drop-text\">Drop files here<br>or click to upload</p>\n          </div>\n        </div>\n      </div>\n      </div>\n    </div>\n  "
  };

  /* ---------------- Main ---------------- */
  var Main = ui_vue3.defineComponent({
    name: "Main",
    components: {
      InputManager: InputManager,
      TileWidgetComponent: ui_uploader_tileWidget.TileWidgetComponent,
      CustomTileWidget: CustomTileWidget
    },
    props: {
      controlId: {
        type: String,
        required: true
      },
      context: {
        type: Object,
        required: true
      },
      // { entityId, fieldName, multiple, readonly }
      filledValues: {
        type: Array,
        "default": function _default() {
          return [];
        }
      },
      // numeric ids OR signed ids
      fileDetails: {
        type: Array,
        "default": function _default() {
          return [];
        }
      } // [{id,name,src,extension,signedFileId,...}]
    },
    setup: function setup(props) {
      var uploaderRef = ui_vue3.ref(null);
      var inputMgrRef = ui_vue3.ref(null);
      var fileData = ui_vue3.ref([]);
      var errorMessage = ui_vue3.ref("");
      var MAX_FILES = 20;
      var fileTokens = ui_vue3.ref(babelHelpers.toConsumableArray(props.filledValues || []));
      var pushTokensToInput = function pushTokensToInput() {
        var _inputMgrRef$value;
        var tokens = [];
        var seen = new Set();
        var _iterator = _createForOfIteratorHelper(fileData.value),
          _step;
        try {
          for (_iterator.s(); !(_step = _iterator.n()).done;) {
            var f = _step.value;
            if (f.uploadStatus !== "completed") continue;
            var t = tokenOf(f);
            if (!t) continue;
            var k = String(t);
            if (!seen.has(k)) {
              seen.add(k);
              tokens.push(t);
            }
          }
        } catch (err) {
          _iterator.e(err);
        } finally {
          _iterator.f();
        }
        console.log("[pushTokensToInput] tokens →", tokens);
        fileTokens.value = tokens;
        (_inputMgrRef$value = inputMgrRef.value) === null || _inputMgrRef$value === void 0 ? void 0 : _inputMgrRef$value.setValues(tokens);
      };

      /** Load BX files' metadata first, then build tiles */
      var syncFromBx = /*#__PURE__*/function () {
        var _ref2 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
          var _uploaderRef$value, _bx$getFiles;
          var bx, bxFiles, loaders, completed, seenTok, inflight, hasFilesWithoutProperData;
          return _regeneratorRuntime().wrap(function _callee2$(_context2) {
            while (1) switch (_context2.prev = _context2.next) {
              case 0:
                bx = (_uploaderRef$value = uploaderRef.value) === null || _uploaderRef$value === void 0 ? void 0 : _uploaderRef$value.uploader;
                if (bx) {
                  _context2.next = 4;
                  break;
                }
                console.log("[syncFromBx] BX uploader not ready yet");
                return _context2.abrupt("return");
              case 4:
                bxFiles = ((_bx$getFiles = bx.getFiles) === null || _bx$getFiles === void 0 ? void 0 : _bx$getFiles.call(bx)) || [];
                console.log("[syncFromBx] bx.getFiles()", bxFiles.length, bxFiles);

                // 1) Ensure metadata is fetched
                loaders = [];
                bxFiles.forEach(function (file, i) {
                  var _file$isComplete;
                  var hasLoad = typeof (file === null || file === void 0 ? void 0 : file.load) === "function";
                  console.log("[syncFromBx] #".concat(i, " isComplete=").concat(file === null || file === void 0 ? void 0 : (_file$isComplete = file.isComplete) === null || _file$isComplete === void 0 ? void 0 : _file$isComplete.call(file), " hasLoad=").concat(hasLoad));
                  if (hasLoad) {
                    try {
                      var p = file.load()["catch"](function (e) {
                        console.warn("[syncFromBx] load() failed for #".concat(i), e);
                      });
                      loaders.push(p);
                    } catch (e) {
                      console.warn("[syncFromBx] load() threw for #".concat(i), e);
                    }
                  }
                });
                if (!loaders.length) {
                  _context2.next = 12;
                  break;
                }
                console.log("[syncFromBx] awaiting ".concat(loaders.length, " load() calls..."));
                _context2.next = 12;
                return Promise.allSettled(loaders);
              case 12:
                // 2) Build completed tiles (name/preview should be available now)
                completed = [];
                seenTok = new Set();
                bxFiles.forEach(function (file, idx) {
                  var _file$getCustomData, _file$getCustomData$c, _file$getServerFileId, _file$getName, _file$getPreviewUrl, _file$getDownloadUrl, _file$getSignedFileId;
                  // after load() Bitrix often marks as "server" (not strictly "complete"),
                  // so rely on having IDs + name instead of isComplete only
                  var real = (_file$getCustomData = file.getCustomData) === null || _file$getCustomData === void 0 ? void 0 : (_file$getCustomData$c = _file$getCustomData.call(file)) === null || _file$getCustomData$c === void 0 ? void 0 : _file$getCustomData$c.realFileId;
                  var id = Number.isFinite(real) ? real : (_file$getServerFileId = file.getServerFileId) === null || _file$getServerFileId === void 0 ? void 0 : _file$getServerFileId.call(file);
                  var name = ((_file$getName = file.getName) === null || _file$getName === void 0 ? void 0 : _file$getName.call(file)) || "File ".concat(id);
                  var ext = toExt(name);
                  var previewUrl = ((_file$getPreviewUrl = file.getPreviewUrl) === null || _file$getPreviewUrl === void 0 ? void 0 : _file$getPreviewUrl.call(file)) || "";
                  var downloadUrl = ((_file$getDownloadUrl = file.getDownloadUrl) === null || _file$getDownloadUrl === void 0 ? void 0 : _file$getDownloadUrl.call(file)) || "";
                  var signed = ((_file$getSignedFileId = file.getSignedFileId) === null || _file$getSignedFileId === void 0 ? void 0 : _file$getSignedFileId.call(file)) || null;
                  var token = signed || id;
                  var isImageExt = ["jpg", "jpeg", "png", "gif", "webp", "svg", "bmp"].includes(ext);
                  var finalSrc = previewUrl || (isImageExt ? downloadUrl : "");
                  console.log("[syncFromBx] resolved", {
                    idx: idx,
                    id: id,
                    name: name,
                    ext: ext,
                    signed: signed,
                    previewUrl: previewUrl,
                    downloadUrl: downloadUrl,
                    finalSrc: finalSrc,
                    hasPreviewMethod: !!file.getPreviewUrl,
                    hasDownloadMethod: !!file.getDownloadUrl
                  });
                  if (token == null || seenTok.has(String(token))) return;
                  seenTok.add(String(token));

                  // Check if this file is already in fileData as an uploading tile
                  var existingIndex = fileData.value.findIndex(function (f) {
                    return String(f.id) === String(id) || f.uploadStatus === "uploading" && f.name === name;
                  });
                  var fileEntry = {
                    id: id,
                    name: name,
                    src: finalSrc || "",
                    isImage: isImageExt && !!finalSrc,
                    extension: ext,
                    uploadStatus: "completed",
                    progress: 100,
                    signedFileId: signed || null
                  };
                  if (existingIndex > -1) {
                    // Update existing uploading tile to completed
                    console.log("[syncFromBx] updating existing tile", {
                      existingIndex: existingIndex,
                      name: name,
                      id: id
                    });
                    fileData.value[existingIndex] = fileEntry;
                  } else {
                    // Add as new completed tile
                    completed.push(fileEntry);
                  }
                });

                // keep uploading tiles that weren't matched/updated
                inflight = fileData.value.filter(function (f) {
                  return f.uploadStatus !== "completed" && !seenTok.has(String(tokenOf(f)));
                }); // Only add new completed tiles (not the updated ones which are already in fileData)
                fileData.value = [].concat(babelHelpers.toConsumableArray(fileData.value.filter(function (f) {
                  return f.uploadStatus === "completed";
                })), completed, babelHelpers.toConsumableArray(inflight));
                console.log("[syncFromBx] fileData →", fileData.value);
                pushTokensToInput();

                // Check if we have files but no proper previews/names - retry after delay
                hasFilesWithoutProperData = completed.some(function (f) {
                  return !f.src && f.isImage || f.name.startsWith('File ');
                });
                if (hasFilesWithoutProperData && !window._syncRetryInProgress) {
                  console.log("[syncFromBx] Detected files without proper previews, scheduling retry...");
                  window._syncRetryInProgress = true;
                  setTimeout( /*#__PURE__*/babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
                    return _regeneratorRuntime().wrap(function _callee$(_context) {
                      while (1) switch (_context.prev = _context.next) {
                        case 0:
                          console.log("[syncFromBx] Retry attempt - reloading file metadata...");
                          _context.next = 3;
                          return syncFromBx();
                        case 3:
                          window._syncRetryInProgress = false;
                        case 4:
                        case "end":
                          return _context.stop();
                      }
                    }, _callee);
                  })), 1000); // Wait 1 second for Bitrix to fully initialize
                }
              case 21:
              case "end":
                return _context2.stop();
            }
          }, _callee2);
        }));
        return function syncFromBx() {
          return _ref2.apply(this, arguments);
        };
      }();
      var uploaderOptions = ui_vue3.computed(function () {
        var _props$context;
        return {
          controller: "main.fileUploader.fieldFileUploaderController",
          // Pass full context to let Bitrix controller hydrate existing tokens
          controllerOptions: props.context,
          files: fileTokens.value,
          multiple: !!((_props$context = props.context) !== null && _props$context !== void 0 && _props$context.multiple),
          autoUpload: true,
          treatOversizeImageAsFile: true,
          events: {
            "File:onAdd": function FileOnAdd(ev) {
              var _ev$getData, _ev$getData$call, _bxFile$getName;
              var bxFile = ev === null || ev === void 0 ? void 0 : (_ev$getData = ev.getData) === null || _ev$getData === void 0 ? void 0 : (_ev$getData$call = _ev$getData.call(ev)) === null || _ev$getData$call === void 0 ? void 0 : _ev$getData$call.file;
              var name = bxFile === null || bxFile === void 0 ? void 0 : (_bxFile$getName = bxFile.getName) === null || _bxFile$getName === void 0 ? void 0 : _bxFile$getName.call(bxFile);
              if (!bxFile || !name) return;
              for (var i = fileData.value.length - 1; i >= 0; i--) {
                var f = fileData.value[i];
                if (f.uploadStatus === "uploading" && f.name === name) {
                  var _bxFile$getId, _bxFile$getServerFile;
                  var newId = ((_bxFile$getId = bxFile.getId) === null || _bxFile$getId === void 0 ? void 0 : _bxFile$getId.call(bxFile)) || ((_bxFile$getServerFile = bxFile.getServerFileId) === null || _bxFile$getServerFile === void 0 ? void 0 : _bxFile$getServerFile.call(bxFile));
                  f.id = newId;
                  f.progress = 0;
                  console.log("[onAdd] temp→BX id", {
                    name: name,
                    newId: newId
                  });
                  break;
                }
              }
            },
            "File:onProgress": function FileOnProgress(ev) {
              var _ev$getData2, _ev$getData2$call, _file$getId, _file$getServerFileId2, _file$getProgress;
              var file = ev === null || ev === void 0 ? void 0 : (_ev$getData2 = ev.getData) === null || _ev$getData2 === void 0 ? void 0 : (_ev$getData2$call = _ev$getData2.call(ev)) === null || _ev$getData2$call === void 0 ? void 0 : _ev$getData2$call.file;
              if (!file) return;
              var id = ((_file$getId = file.getId) === null || _file$getId === void 0 ? void 0 : _file$getId.call(file)) || ((_file$getServerFileId2 = file.getServerFileId) === null || _file$getServerFileId2 === void 0 ? void 0 : _file$getServerFileId2.call(file));
              var idx = fileData.value.findIndex(function (x) {
                return String(x.id) === String(id);
              });
              if (idx > -1) fileData.value[idx].progress = ((_file$getProgress = file.getProgress) === null || _file$getProgress === void 0 ? void 0 : _file$getProgress.call(file)) || 0;
            },
            "File:onUploadComplete": function FileOnUploadComplete() {
              console.log("[onUploadComplete]");
              setTimeout(syncFromBx, 0);
            },
            onUploadComplete: function onUploadComplete() {
              console.log("[onUploadComplete (alt)]");
              setTimeout(syncFromBx, 0);
            },
            "File:onRemove": function FileOnRemove(ev) {
              var _ev$getData3, _ev$getData3$call, _file$getServerFileId3, _file$getCustomData2, _file$getCustomData2$, _inputMgrRef$value2;
              var file = ev === null || ev === void 0 ? void 0 : (_ev$getData3 = ev.getData) === null || _ev$getData3 === void 0 ? void 0 : (_ev$getData3$call = _ev$getData3.call(ev)) === null || _ev$getData3$call === void 0 ? void 0 : _ev$getData3$call.file;
              if (!file) return;
              var serverId = (_file$getServerFileId3 = file.getServerFileId) === null || _file$getServerFileId3 === void 0 ? void 0 : _file$getServerFileId3.call(file);
              var realId = (_file$getCustomData2 = file.getCustomData) === null || _file$getCustomData2 === void 0 ? void 0 : (_file$getCustomData2$ = _file$getCustomData2.call(file)) === null || _file$getCustomData2$ === void 0 ? void 0 : _file$getCustomData2$.realFileId;
              var rmToken = String(Number.isFinite(realId) ? realId : serverId);
              console.log("[onRemove] token", rmToken);
              (_inputMgrRef$value2 = inputMgrRef.value) === null || _inputMgrRef$value2 === void 0 ? void 0 : _inputMgrRef$value2.addDeleted(rmToken);
              fileData.value = fileData.value.filter(function (f) {
                var _file$getId2;
                return String(tokenOf(f)) !== rmToken && String(f.id) !== String((_file$getId2 = file.getId) === null || _file$getId2 === void 0 ? void 0 : _file$getId2.call(file));
              });
              pushTokensToInput();
            }
          }
        };
      });

      /* ---------------- UI handlers ---------------- */
      var handleUpload = function handleUpload(fileList) {
        var _uploaderRef$value2;
        var bx = (_uploaderRef$value2 = uploaderRef.value) === null || _uploaderRef$value2 === void 0 ? void 0 : _uploaderRef$value2.uploader;
        if (!bx || !fileList) return;

        // Clear previous error messages
        errorMessage.value = "";
        var filesToUpload = babelHelpers.toConsumableArray(fileList);
        var currentFileCount = fileData.value.length;
        var totalAfterUpload = currentFileCount + filesToUpload.length;

        // Check if adding these files would exceed the limit
        if (totalAfterUpload > MAX_FILES) {
          var allowed = Math.max(0, MAX_FILES - currentFileCount);
          errorMessage.value = "Maximum ".concat(MAX_FILES, " files allowed. You can only upload ").concat(allowed, " more file(s).");
          console.warn("[handleUpload] File limit exceeded", {
            current: currentFileCount,
            trying: filesToUpload.length,
            max: MAX_FILES,
            allowed: allowed
          });

          // Clear the error message after 5 seconds
          setTimeout(function () {
            errorMessage.value = "";
          }, 5000);
          return;
        }
        filesToUpload.forEach(function (f) {
          var _f$type, _f$type2;
          var tmpId = "tmp_".concat(Date.now(), "_").concat(Math.random().toString(36).slice(2));
          var ext = toExt(f.name);
          var isImage = ((_f$type = f.type) === null || _f$type === void 0 ? void 0 : _f$type.startsWith("image/")) || ["jpg", "jpeg", "png", "gif", "webp", "svg", "bmp"].includes(ext);
          var previewSrc = "";
          if (isImage && (_f$type2 = f.type) !== null && _f$type2 !== void 0 && _f$type2.startsWith("image/")) {
            try {
              previewSrc = URL.createObjectURL(f);
            } catch (_unused) {}
          }
          console.log("[handleUpload] temp tile", {
            name: f.name,
            ext: ext,
            isImage: isImage,
            previewSrc: previewSrc
          });
          fileData.value.push({
            id: tmpId,
            name: f.name,
            size: f.size,
            src: previewSrc,
            isImage: isImage,
            extension: ext,
            uploadStatus: "uploading",
            progress: 0
          });
          bx.addFile(f);
        });
      };
      var handleReorder = function handleReorder(reordered) {
        var seen = new Set();
        var completed = [];
        var inflight = [];
        var _iterator2 = _createForOfIteratorHelper(reordered),
          _step2;
        try {
          for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
            var f = _step2.value;
            if (f.uploadStatus !== "completed") {
              inflight.push(f);
              continue;
            }
            var t = tokenOf(f);
            if (!t) continue;
            var k = String(t);
            if (!seen.has(k)) {
              seen.add(k);
              completed.push(f);
            }
          }
        } catch (err) {
          _iterator2.e(err);
        } finally {
          _iterator2.f();
        }
        fileData.value = [].concat(babelHelpers.toConsumableArray(completed.map(function (f) {
          return _objectSpread({}, f);
        })), babelHelpers.toConsumableArray(inflight.map(function (f) {
          return _objectSpread({}, f);
        })));
        ui_vue3.nextTick(pushTokensToInput);
        ui_vue3.nextTick(function () {
          var _inputMgrRef$value3;
          return (_inputMgrRef$value3 = inputMgrRef.value) === null || _inputMgrRef$value3 === void 0 ? void 0 : _inputMgrRef$value3.updateOrder(fileTokens.value);
        });
      };
      var handleRemove = function handleRemove(fileId) {
        var _uploaderRef$value3, _inputMgrRef$value4;
        var bx = (_uploaderRef$value3 = uploaderRef.value) === null || _uploaderRef$value3 === void 0 ? void 0 : _uploaderRef$value3.uploader;
        if (bx) {
          var f = bx.getFiles().find(function (ff) {
            var _ff$getServerFileId, _ff$getId;
            return String((_ff$getServerFileId = ff.getServerFileId) === null || _ff$getServerFileId === void 0 ? void 0 : _ff$getServerFileId.call(ff)) === String(fileId) || String((_ff$getId = ff.getId) === null || _ff$getId === void 0 ? void 0 : _ff$getId.call(ff)) === String(fileId);
          });
          if (f) bx.removeFile(f);
        }
        var removed = fileData.value.find(function (f) {
          return String(f.id) === String(fileId);
        });
        fileData.value = fileData.value.filter(function (f) {
          return String(f.id) !== String(fileId);
        });
        (_inputMgrRef$value4 = inputMgrRef.value) === null || _inputMgrRef$value4 === void 0 ? void 0 : _inputMgrRef$value4.addDeleted(removed ? removed.signedFileId || removed.id : fileId);
        pushTokensToInput();
      };

      /* ---------------- lifecycle ---------------- */
      ui_vue3.onMounted(function () {
        var _inputMgrRef$value6, _valueChanger$closest;
        if (main_core.Type.isArrayFilled(props.fileDetails)) {
          fileData.value = props.fileDetails.map(function (f) {
            return {
              id: f.id,
              name: f.name,
              src: f.src || "",
              isImage: !!f.src,
              extension: toExt(f.name || ""),
              uploadStatus: "completed",
              progress: 100,
              signedFileId: f.signedFileId || null
            };
          });
          console.log("[onMounted] boot from fileDetails", fileData.value);
          pushTokensToInput();
        } else {
          console.log("[onMounted] No fileDetails provided, will use Bitrix sync");
        }
        ui_vue3.nextTick(function () {
          var _inputMgrRef$value5;
          console.log("[onMounted] seed InputManager with tokens", fileTokens.value);
          (_inputMgrRef$value5 = inputMgrRef.value) === null || _inputMgrRef$value5 === void 0 ? void 0 : _inputMgrRef$value5.setValues(fileTokens.value);
        });

        // Poll BX a bit, then force a metadata load+sync
        var tries = 0;
        var maxTries = 40; // ~4s
        var poll = /*#__PURE__*/function () {
          var _ref4 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3() {
            var _uploaderRef$value4, _bx$getFiles2, _props$filledValues, _props$filledValues2;
            var bx, list;
            return _regeneratorRuntime().wrap(function _callee3$(_context3) {
              while (1) switch (_context3.prev = _context3.next) {
                case 0:
                  bx = (_uploaderRef$value4 = uploaderRef.value) === null || _uploaderRef$value4 === void 0 ? void 0 : _uploaderRef$value4.uploader;
                  list = (bx === null || bx === void 0 ? void 0 : (_bx$getFiles2 = bx.getFiles) === null || _bx$getFiles2 === void 0 ? void 0 : _bx$getFiles2.call(bx)) || [];
                  console.log("[poll] bx_ready=".concat(!!bx, " files=").concat(list.length, " try=").concat(tries));

                  // TEMPORARILY DISABLED - Skip Bitrix sync to test our mock data
                  // if (fileData.value.length > 0) {
                  //   console.log("[poll] Using test data, skipping BX sync");
                  //   return;
                  // }
                  if (!(!bx || (_props$filledValues = props.filledValues) !== null && _props$filledValues !== void 0 && _props$filledValues.length && list.length === 0)) {
                    _context3.next = 6;
                    break;
                  }
                  if (!(tries++ < maxTries)) {
                    _context3.next = 6;
                    break;
                  }
                  return _context3.abrupt("return", void setTimeout(poll, 100));
                case 6:
                  _context3.next = 8;
                  return syncFromBx();
                case 8:
                  if (!(fileData.value.length === 0 && (_props$filledValues2 = props.filledValues) !== null && _props$filledValues2 !== void 0 && _props$filledValues2.length && tries < maxTries)) {
                    _context3.next = 10;
                    break;
                  }
                  return _context3.abrupt("return", void setTimeout(poll, 200));
                case 10:
                case "end":
                  return _context3.stop();
              }
            }, _callee3);
          }));
          return function poll() {
            return _ref4.apply(this, arguments);
          };
        }();
        setTimeout(poll, 0);
        var valueChanger = (_inputMgrRef$value6 = inputMgrRef.value) === null || _inputMgrRef$value6 === void 0 ? void 0 : _inputMgrRef$value6.$refs.valueChanger;
        var form = valueChanger === null || valueChanger === void 0 ? void 0 : (_valueChanger$closest = valueChanger.closest) === null || _valueChanger$closest === void 0 ? void 0 : _valueChanger$closest.call(valueChanger, "form");
        if (form) {
          form.addEventListener("submit", function (e) {
            var pending = fileData.value.some(function (f) {
              return f.uploadStatus === "uploading";
            });
            if (pending) {
              console.warn("[submit] blocked: uploads pending");
              e.preventDefault();
              return;
            }
            console.log("[submit] commit tokens");
            pushTokensToInput();
          }, {
            capture: true
          });
        }
      });
      return {
        uploaderRef: uploaderRef,
        inputMgrRef: inputMgrRef,
        fileData: fileData,
        fileTokens: fileTokens,
        uploaderOptions: uploaderOptions,
        handleUpload: handleUpload,
        handleReorder: handleReorder,
        handleRemove: handleRemove,
        context: props.context,
        errorMessage: errorMessage
      };
    },
    template: "\n    <div class=\"main-field-file-wrapper\">\n      <InputManager\n        ref=\"inputMgrRef\"\n        :controlId=\"controlId\"\n        :controlName=\"context.fieldName\"\n        :multiple=\"context.multiple\"\n        :filledValues=\"filledValues\"\n      />\n\n      <!-- Hidden Bitrix uploader (engine only) -->\n      <TileWidgetComponent\n        ref=\"uploaderRef\"\n        :uploaderOptions=\"uploaderOptions\"\n        style=\"display:none\"\n      />\n\n      <!-- Visible custom tiles -->\n      <CustomTileWidget\n        :files=\"fileData\"\n        :readonly=\"context.readonly || false\"\n        @upload=\"handleUpload\"\n        @reorder=\"handleReorder\"\n        @remove=\"handleRemove\"\n      />\n\n      <!-- Error message display -->\n      <div v-if=\"errorMessage\" class=\"file-upload-error\" style=\"color: #d32f2f; background: #ffebee; padding: 8px 12px; border-radius: 4px; margin-top: 8px; border-left: 3px solid #d32f2f;\">\n        {{ errorMessage }}\n      </div>\n\n      <div v-if=\"!fileData.length\" class=\"no-files-message\">No files uploaded yet.</div>\n    </div>\n  "
  });

  function ownKeys$1(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
  function _objectSpread$1(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$1(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$1(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
  function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
  function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
  var _controlId = /*#__PURE__*/new WeakMap();
  var _container = /*#__PURE__*/new WeakMap();
  var _context = /*#__PURE__*/new WeakMap();
  var _value = /*#__PURE__*/new WeakMap();
  var _app = /*#__PURE__*/new WeakMap();
  var App = /*#__PURE__*/function () {
    function App(params) {
      babelHelpers.classCallCheck(this, App);
      _classPrivateFieldInitSpec(this, _controlId, {
        writable: true,
        value: void 0
      });
      _classPrivateFieldInitSpec(this, _container, {
        writable: true,
        value: void 0
      });
      _classPrivateFieldInitSpec(this, _context, {
        writable: true,
        value: void 0
      });
      _classPrivateFieldInitSpec(this, _value, {
        writable: true,
        value: void 0
      });
      _classPrivateFieldInitSpec(this, _app, {
        writable: true,
        value: null
      });
      babelHelpers.classPrivateFieldSet(this, _controlId, params.controlId);
      babelHelpers.classPrivateFieldSet(this, _container, document.getElementById(params.containerId));
      if (!main_core.Type.isDomNode(babelHelpers.classPrivateFieldGet(this, _container))) {
        throw new Error('container not found');
      }
      babelHelpers.classPrivateFieldSet(this, _context, params.context);
      babelHelpers.classPrivateFieldSet(this, _value, params.value.map(function (value) {
        return parseInt(value);
      }));
    }
    babelHelpers.createClass(App, [{
      key: "start",
      value: function start() {
        babelHelpers.classPrivateFieldSet(this, _app, ui_vue3.BitrixVue.createApp(_objectSpread$1({}, Main), {
          controlId: babelHelpers.classPrivateFieldGet(this, _controlId),
          container: babelHelpers.classPrivateFieldGet(this, _container),
          context: babelHelpers.classPrivateFieldGet(this, _context),
          filledValues: babelHelpers.classPrivateFieldGet(this, _value)
        }));
        babelHelpers.classPrivateFieldGet(this, _app).mount(babelHelpers.classPrivateFieldGet(this, _container));
      }
    }, {
      key: "stop",
      value: function stop() {
        babelHelpers.classPrivateFieldGet(this, _app).unmount();
        babelHelpers.classPrivateFieldSet(this, _app, null);
      }
    }]);
    return App;
  }();

  exports.App = App;

}((this.BX.Main.Field.File = this.BX.Main.Field.File || {}),BX,BX.Vue3,BX.UI.Uploader));
//# sourceMappingURL=script.js.map
