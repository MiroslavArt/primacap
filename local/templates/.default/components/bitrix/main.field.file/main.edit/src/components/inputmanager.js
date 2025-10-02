// inputmanager.js
export const InputManager = {
  name: "InputManager",
  props: {
    controlId: { type: String, required: true },
    controlName: { type: String, required: true }, // e.g. "UF_CRM_XXXXX"
    multiple: { type: Boolean, required: true },
    filledValues: { type: Array, default: () => [] }, // initial tokens
  },
  data() {
    return {
      // keep as strings to avoid 291 === "291" mismatches
      values: Array.isArray(this.filledValues)
        ? this.filledValues.map(String)
        : [],
      deletedValues: [],
    };
  },
  methods: {
    fireChange() {
      // notify Bitrix editor that hidden inputs changed
      BX.fireEvent(this.$refs.valueChanger, "change");
    },

    // Replace the token list (already ordered & deduped by caller)
    setValues(newValues) {
      const next = Array.from(newValues || []).map(String);
      const prev = this.values;
      this.values = next;
      if (!this.arraysAreEqual(prev, this.values)) this.fireChange();
    },

    // Reorder-only variant (same semantics as setValues)
    updateOrder(newOrder) {
      const next = Array.from(newOrder || []).map(String);
      const prev = this.values;
      this.values = next;
      if (!this.arraysAreEqual(prev, this.values)) this.fireChange();
    },

    addDeleted(fileToken) {
      const t = String(fileToken);
      this.deletedValues = [...this.deletedValues, t];
      this.fireChange();
    },

    removeValue(fileToken) {
      const t = String(fileToken);
      const idx = this.values.indexOf(t);
      if (idx > -1) {
        this.values.splice(idx, 1);
        this.fireChange();
      }
    },

    arraysAreEqual(a, b) {
      if (!Array.isArray(a) || !Array.isArray(b)) return false;
      if (a.length !== b.length) return false;
      for (let i = 0; i < a.length; i++) {
        if (a[i] !== b[i]) return false;
      }
      return true;
    },
  },

  template: `
    <input ref="valueChanger" type="hidden" />
    <div class="uf-hidden-inputs" style="display: none;">
      <!-- tokens -->
      <div v-if="values.length">
        <input
          v-if="multiple"
          v-for="(val, i) in values"
          :key="'v-'+i"
          type="hidden"
          :name="controlName + '[]'"
          :value="val"
        />
        <input
          v-else
          type="hidden"
          :name="controlName"
          :value="values[values.length - 1]"
        />
      </div>
      <div v-else>
        <input type="hidden" :name="multiple ? controlName + '[]' : controlName" />
      </div>

      <!-- deletions -->
      <div v-if="deletedValues.length">
        <input
          v-if="multiple"
          v-for="(val, i) in deletedValues"
          :key="'d1-'+i"
          type="hidden"
          :name="controlName + '_del[]'"
          :value="val"
        />
        <input
          v-else
          type="hidden"
          :name="controlName + '_del'"
          :value="deletedValues[0]"
        />
        <input
          v-for="(val, i) in deletedValues"
          :key="'d2-'+i"
          type="hidden"
          :name="controlId + '_deleted[]'"
          :value="val"
        />
      </div>
    </div>
  `,
};
