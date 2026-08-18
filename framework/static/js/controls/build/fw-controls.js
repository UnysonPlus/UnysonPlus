(() => {
  // ../framework/static/js/controls/src/registry.js
  var controls = /* @__PURE__ */ new Map();
  function register(type, component) {
    if (typeof type !== "string" || !type) {
      throw new Error("fw.controls.register: type must be a non-empty string");
    }
    if (typeof component !== "function") {
      throw new Error(`fw.controls.register: component for "${type}" must be a function`);
    }
    controls.set(type, component);
  }
  function get(type) {
    return controls.get(type) || null;
  }
  function has(type) {
    return controls.has(type);
  }
  function types() {
    return Array.from(controls.keys()).sort();
  }

  // ../framework/static/js/controls/src/controls/text.jsx
  var { TextControl } = wp.components;
  function Text({ option = {}, value = "", onChange }) {
    return /* @__PURE__ */ wp.element.createElement(
      TextControl,
      {
        label: option.label || "",
        help: option.desc || void 0,
        value: value != null ? value : "",
        placeholder: option.attr && option.attr.placeholder || void 0,
        onChange,
        __next40pxDefaultSize: true,
        __nextHasNoMarginBottom: true
      }
    );
  }

  // ../framework/static/js/controls/src/controls/switch.jsx
  var { ToggleControl } = wp.components;
  var LEFT = { value: false, label: "No" };
  var RIGHT = { value: true, label: "Yes" };
  function Switch({ option = {}, value, onChange }) {
    const left = option["left-choice"] || LEFT;
    const right = option["right-choice"] || RIGHT;
    const same = (a, b) => JSON.stringify(a) === JSON.stringify(b);
    const checked = same(value, right.value);
    return /* @__PURE__ */ wp.element.createElement(
      ToggleControl,
      {
        label: option.label || "",
        help: option.desc || (checked ? right.label : left.label),
        checked,
        onChange: (next) => onChange(next ? right.value : left.value),
        __nextHasNoMarginBottom: true
      }
    );
  }

  // ../framework/static/js/controls/src/controls/select.jsx
  var { SelectControl } = wp.components;
  function Select({ option = {}, value, onChange }) {
    var _a;
    const choices = option.choices || {};
    const options = Object.keys(choices).map((key) => {
      const choice = choices[key];
      return {
        value: key,
        // A choice may be a plain label string, or a { text|label } object
        // (some option types decorate choices with extra metadata).
        label: typeof choice === "string" ? choice : choice && (choice.text || choice.label) || key
      };
    });
    return /* @__PURE__ */ wp.element.createElement(
      SelectControl,
      {
        label: option.label || "",
        help: option.desc || void 0,
        value: (_a = value != null ? value : option.value) != null ? _a : "",
        options,
        onChange,
        __next40pxDefaultSize: true,
        __nextHasNoMarginBottom: true
      }
    );
  }

  // ../framework/static/js/controls/src/controls/upload.jsx
  var { Button, BaseControl, Flex, FlexItem } = wp.components;
  function getMediaUpload() {
    if (wp.mediaUtils && wp.mediaUtils.MediaUpload) {
      return wp.mediaUtils.MediaUpload;
    }
    if (wp.blockEditor && wp.blockEditor.MediaUpload) {
      return wp.blockEditor.MediaUpload;
    }
    return null;
  }
  function toProtocolRelative(url) {
    return typeof url === "string" ? url.replace(/^https?:\/\//, "//") : "";
  }
  function toDisplayUrl(url) {
    return typeof url === "string" && url.startsWith("//") ? window.location.protocol + url : url;
  }
  function Upload({ option = {}, value, onChange }) {
    const MediaUpload = getMediaUpload();
    const current = value && value.url ? value : null;
    if (!MediaUpload) {
      return /* @__PURE__ */ wp.element.createElement(BaseControl, { label: option.label || "", __nextHasNoMarginBottom: true }, /* @__PURE__ */ wp.element.createElement("p", null, "The media library is not available on this screen."));
    }
    const onSelect = (media) => {
      onChange({
        attachment_id: media.id,
        url: toProtocolRelative(media.url)
      });
    };
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      current && /* @__PURE__ */ wp.element.createElement(
        "img",
        {
          src: toDisplayUrl(current.url),
          alt: "",
          style: {
            display: "block",
            maxWidth: "100%",
            height: "auto",
            marginBottom: "8px",
            borderRadius: "2px"
          }
        }
      ),
      /* @__PURE__ */ wp.element.createElement(
        MediaUpload,
        {
          onSelect,
          allowedTypes: option.images_only === false ? void 0 : ["image"],
          value: current ? current.attachment_id : void 0,
          render: ({ open }) => /* @__PURE__ */ wp.element.createElement(Flex, { justify: "flex-start", gap: 2 }, /* @__PURE__ */ wp.element.createElement(FlexItem, null, /* @__PURE__ */ wp.element.createElement(Button, { variant: "secondary", onClick: open }, current ? "Replace" : "Select image")), current && /* @__PURE__ */ wp.element.createElement(FlexItem, null, /* @__PURE__ */ wp.element.createElement(
            Button,
            {
              variant: "tertiary",
              isDestructive: true,
              onClick: () => onChange("")
            },
            "Remove"
          )))
        }
      )
    );
  }

  // ../framework/static/js/controls/src/controls/textarea.jsx
  var { TextareaControl } = wp.components;
  function Textarea({ option = {}, value = "", onChange }) {
    const attr = option.attr || {};
    return /* @__PURE__ */ wp.element.createElement(
      TextareaControl,
      {
        label: option.label || "",
        help: option.desc || void 0,
        value: value != null ? value : "",
        placeholder: attr.placeholder || void 0,
        rows: attr.rows ? Number(attr.rows) : void 0,
        onChange,
        __nextHasNoMarginBottom: true
      }
    );
  }

  // ../framework/static/js/controls/src/controls/radio.jsx
  var { RadioControl } = wp.components;
  function Radio({ option = {}, value, onChange }) {
    var _a;
    const choices = option.choices || {};
    const options = Object.keys(choices).map((key) => {
      const choice = choices[key];
      return {
        value: key,
        // A choice may be a plain label string, or a { text|label } object —
        // matching the leniency the select control already applies.
        label: typeof choice === "string" ? choice : choice && (choice.text || choice.label) || key
      };
    });
    const selected = (_a = value != null ? value : option.value) != null ? _a : "";
    return /* @__PURE__ */ wp.element.createElement(
      RadioControl,
      {
        label: option.label || "",
        help: option.desc || void 0,
        selected: String(selected),
        options,
        onChange
      }
    );
  }

  // ../framework/static/js/controls/src/controls/checkbox.jsx
  var { CheckboxControl, BaseControl: BaseControl2 } = wp.components;
  function Checkbox({ option = {}, value, onChange }) {
    const checked = !!value;
    const caption = option.text || option.label || "";
    const heading = option.label && option.label !== caption ? option.label : "";
    const control = /* @__PURE__ */ wp.element.createElement(
      CheckboxControl,
      {
        label: caption,
        help: heading ? void 0 : option.desc || void 0,
        checked,
        onChange: (next) => onChange(!!next),
        __nextHasNoMarginBottom: true
      }
    );
    if (!heading) {
      return control;
    }
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl2,
      {
        label: heading,
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      control
    );
  }

  // ../framework/static/js/controls/src/controls/color-picker.jsx
  var { ColorPicker, BaseControl: BaseControl3, Button: Button2, Flex: Flex2, FlexItem: FlexItem2 } = wp.components;
  var { useState } = wp.element;
  function toHex(raw) {
    const input = String(raw != null ? raw : "").trim();
    if (!input) {
      return "";
    }
    if (/^#([a-f0-9]{3}|[a-f0-9]{4}|[a-f0-9]{6}|[a-f0-9]{8})$/i.test(input)) {
      return input;
    }
    const m = input.match(
      /^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*(?:,\s*([\d.]+)\s*)?\)$/i
    );
    if (!m) {
      return "";
    }
    const hx = (n) => Math.max(0, Math.min(255, Math.round(Number(n)))).toString(16).padStart(2, "0");
    const rgb = `#${hx(m[1])}${hx(m[2])}${hx(m[3])}`;
    const alpha = m[4] === void 0 ? 1 : Number(m[4]);
    if (!Number.isFinite(alpha) || alpha >= 1) {
      return rgb;
    }
    return rgb + hx(Math.max(0, Math.min(1, alpha)) * 255);
  }
  function ColorPickerControl({ option = {}, value = "", onChange }) {
    const [open, setOpen] = useState(false);
    const current = value != null ? value : "";
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl3,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement(Flex2, { justify: "flex-start", align: "center", gap: 2 }, /* @__PURE__ */ wp.element.createElement(FlexItem2, null, /* @__PURE__ */ wp.element.createElement(
        Button2,
        {
          variant: "secondary",
          onClick: () => setOpen(!open),
          "aria-expanded": open,
          style: {
            // A swatch the user can actually see, including "unset".
            background: current || "transparent",
            border: "1px solid #949494",
            minWidth: 40,
            height: 30
          }
        },
        current ? "" : "\u2014"
      )), /* @__PURE__ */ wp.element.createElement(FlexItem2, null, /* @__PURE__ */ wp.element.createElement("code", null, current || "unset")), current && /* @__PURE__ */ wp.element.createElement(FlexItem2, null, /* @__PURE__ */ wp.element.createElement(Button2, { variant: "tertiary", onClick: () => onChange("") }, "Clear"))),
      open && /* @__PURE__ */ wp.element.createElement(
        ColorPicker,
        {
          color: current || void 0,
          enableAlpha: !!option.alpha,
          onChange: (next) => onChange(toHex(next))
        }
      )
    );
  }

  // ../framework/static/js/controls/src/controls/slider.jsx
  var { RangeControl } = wp.components;
  var DEFAULTS = { min: 0, max: 100, step: 1 };
  function Slider({ option = {}, value, onChange }) {
    var _a, _b, _c, _d;
    const props = option.properties || {};
    const min = Number((_a = props.min) != null ? _a : DEFAULTS.min);
    const max = Number((_b = props.max) != null ? _b : DEFAULTS.max);
    const step = Number((_c = props.step) != null ? _c : DEFAULTS.step);
    const current = Number((_d = value != null ? value : option.value) != null ? _d : min);
    return /* @__PURE__ */ wp.element.createElement(
      RangeControl,
      {
        label: option.label || "",
        help: option.desc || void 0,
        value: Number.isFinite(current) ? current : min,
        min,
        max,
        step,
        onChange: (next) => {
          var _a2;
          return onChange(Number((_a2 = next != null ? next : option.value) != null ? _a2 : min));
        },
        __next40pxDefaultSize: true,
        __nextHasNoMarginBottom: true
      }
    );
  }

  // ../framework/static/js/controls/src/controls/unit-input.jsx
  var { __experimentalNumberControl: NumberControl, SelectControl: SelectControl2, BaseControl: BaseControl4, Flex: Flex3, FlexItem: FlexItem3 } = wp.components;
  var DEFAULT_UNITS = ["px", "em", "rem"];
  function normalizeUnits(units) {
    const source = units && (Array.isArray(units) ? units.length : Object.keys(units).length) ? units : DEFAULT_UNITS;
    const out = [];
    if (Array.isArray(source)) {
      source.forEach((entry) => {
        const value = String(entry).trim();
        if (value) {
          out.push({ value, label: value });
        }
      });
    } else {
      Object.keys(source).forEach((key) => {
        var _a;
        const value = String(key).trim();
        if (value) {
          const label = String((_a = source[key]) != null ? _a : "").trim();
          out.push({ value, label: label || value });
        }
      });
    }
    return out.length ? out : DEFAULT_UNITS.map((u) => ({ value: u, label: u }));
  }
  function UnitInput({ option = {}, value, onChange }) {
    const units = normalizeUnits(option.units);
    const fallback = option.value && typeof option.value === "object" ? option.value : { value: "", unit: units[0].value };
    const current = value && typeof value === "object" ? value : fallback;
    const num = current.value === void 0 || current.value === null ? "" : String(current.value);
    const unit = units.some((u) => u.value === current.unit) ? current.unit : units[0].value;
    const emit = (nextNum, nextUnit) => onChange({ value: nextNum, unit: nextUnit });
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl4,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement(Flex3, { align: "flex-end", gap: 2 }, /* @__PURE__ */ wp.element.createElement(FlexItem3, { isBlock: true }, /* @__PURE__ */ wp.element.createElement(
        NumberControl,
        {
          value: num,
          min: option.min !== "" && option.min !== void 0 ? Number(option.min) : void 0,
          max: option.max !== "" && option.max !== void 0 ? Number(option.max) : void 0,
          step: option.step !== "" && option.step !== void 0 ? Number(option.step) : void 0,
          onChange: (next) => {
            const raw = next === void 0 || next === null ? "" : String(next).trim();
            emit(raw === "" || !isNaN(Number(raw)) ? raw : "", unit);
          },
          __next40pxDefaultSize: true
        }
      )), /* @__PURE__ */ wp.element.createElement(FlexItem3, null, /* @__PURE__ */ wp.element.createElement(
        SelectControl2,
        {
          value: unit,
          options: units,
          onChange: (nextUnit) => emit(num, nextUnit),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      )))
    );
  }

  // ../framework/static/js/controls/src/controls/multi-select.jsx
  var { FormTokenField, BaseControl: BaseControl5, Notice } = wp.components;
  var DELIMITER = "/*/";
  function MultiSelect({ option = {}, value, onChange }) {
    const population = option.population || "array";
    if ("array" !== population) {
      return /* @__PURE__ */ wp.element.createElement(Notice, { status: "warning", isDismissible: false }, `This "${population}" list is built on the server \u2014 edit it in the page builder.`);
    }
    const choices = option.choices || {};
    const labelOf = {};
    const keyOf = {};
    Object.keys(choices).forEach((key) => {
      const choice = choices[key];
      const label = typeof choice === "string" ? choice : choice && (choice.text || choice.label) || key;
      labelOf[key] = label;
      keyOf[label] = key;
    });
    const selectedKeys = Array.isArray(value) ? value : String(value != null ? value : "").split(DELIMITER).filter((k) => "" !== k);
    const selectedLabels = selectedKeys.map((k) => {
      var _a;
      return (_a = labelOf[k]) != null ? _a : k;
    });
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl5,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement(
        FormTokenField,
        {
          value: selectedLabels,
          suggestions: Object.values(labelOf),
          onChange: (tokens) => {
            const keys = tokens.map((token) => {
              var _a;
              return (_a = keyOf[token]) != null ? _a : token;
            }).filter((k) => Object.prototype.hasOwnProperty.call(labelOf, k));
            onChange(keys.join(DELIMITER));
          },
          __experimentalExpandOnFocus: true,
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      )
    );
  }

  // ../framework/static/js/controls/src/controls/image-picker.jsx
  var { BaseControl: BaseControl6, Button: Button3 } = wp.components;
  function flattenChoices(choices) {
    const out = [];
    Object.keys(choices || {}).forEach((key) => {
      const choice = choices[key];
      if (choice && typeof choice === "object" && choice.choices && typeof choice.choices === "object") {
        out.push(...flattenChoices(choice.choices));
        return;
      }
      let src = "";
      let alt = key;
      if (typeof choice === "string") {
        src = choice;
      } else if (choice && typeof choice === "object") {
        const small = choice.small;
        if (typeof small === "string") {
          src = small;
        } else if (small && typeof small === "object") {
          src = small.src || "";
          alt = small.alt || key;
        }
        if (!src && typeof choice.large === "string") {
          src = choice.large;
        }
      }
      out.push({ key, label: alt, src });
    });
    return out;
  }
  function ImagePicker({ option = {}, value, onChange }) {
    var _a;
    const tiles = flattenChoices(option.choices);
    const multiple = !!option.multiple;
    const blank = !!option.blank;
    const selected = multiple ? Array.isArray(value) ? value : [] : (_a = value != null ? value : option.value) != null ? _a : "";
    const isOn = (key) => multiple ? selected.includes(key) : selected === key;
    const toggle = (key) => {
      if (multiple) {
        const next = selected.includes(key) ? selected.filter((k) => k !== key) : tiles.map((t) => t.key).filter((k) => k === key || selected.includes(k));
        onChange(next);
        return;
      }
      onChange(blank && selected === key ? "" : key);
    };
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl6,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement("div", { style: { display: "flex", flexWrap: "wrap", gap: 8 } }, tiles.map((tile) => /* @__PURE__ */ wp.element.createElement(
        Button3,
        {
          key: tile.key,
          onClick: () => toggle(tile.key),
          "aria-pressed": isOn(tile.key),
          label: tile.label,
          showTooltip: true,
          style: {
            padding: 2,
            height: "auto",
            border: isOn(tile.key) ? "2px solid var(--wp-admin-theme-color, #2271b1)" : "2px solid transparent",
            borderRadius: 4,
            outline: isOn(tile.key) ? "none" : "1px solid #ddd"
          }
        },
        tile.src ? /* @__PURE__ */ wp.element.createElement("img", { src: tile.src, alt: tile.label, style: { display: "block", maxWidth: 72, height: "auto" } }) : /* @__PURE__ */ wp.element.createElement("span", { style: { display: "block", padding: "8px 10px", fontSize: 12 } }, tile.label)
      )))
    );
  }

  // ../framework/static/js/controls/src/controls/spacing.jsx
  var { BaseControl: BaseControl7, SelectControl: SelectControl3, Button: Button4, ButtonGroup, Flex: Flex4, FlexItem: FlexItem4 } = wp.components;
  var { useState: useState2 } = wp.element;
  var SLOTS = ["all", "top", "right", "bottom", "left"];
  var SECTIONS = ["margin", "padding"];
  var DEVICES = [
    { key: "base", label: "Base" },
    { key: "md", label: "md \u2265768" },
    { key: "lg", label: "lg \u2265992" }
  ];
  var DEFAULT_SCALE = [
    { name: "0", size: "0" },
    { name: "1", size: "0.25rem" },
    { name: "2", size: "0.5rem" },
    { name: "3", size: "1rem" },
    { name: "4", size: "1.5rem" },
    { name: "5", size: "3rem" }
  ];
  function emptySection() {
    return { all: "", top: "", right: "", bottom: "", left: "" };
  }
  function normalize(value) {
    const v = value && typeof value === "object" ? value : {};
    const section = (s) => Object.assign(emptySection(), s && typeof s === "object" ? s : {});
    const adv = v.advanced && typeof v.advanced === "object" ? v.advanced : {};
    return {
      margin: section(v.margin),
      padding: section(v.padding),
      advanced: {
        md: { margin: section(adv.md && adv.md.margin), padding: section(adv.md && adv.md.padding) },
        lg: { margin: section(adv.lg && adv.lg.margin), padding: section(adv.lg && adv.lg.padding) }
      }
    };
  }
  function Spacing({ option = {}, value, onChange }) {
    const [device, setDevice] = useState2("base");
    const mode = ["both", "margin", "padding"].includes(option.mode) ? option.mode : "both";
    const sections = "both" === mode ? SECTIONS : [mode];
    const scale = Array.isArray(option.scale) && option.scale.length ? option.scale : DEFAULT_SCALE;
    const choices = [{ value: "", label: "\u2014" }].concat(
      scale.map((s) => ({
        value: String(s.name),
        label: `${s.name}${s.size ? ` (${s.size})` : ""}`
      }))
    );
    const current = normalize(value);
    const read = (section, slot) => "base" === device ? current[section][slot] : current.advanced[device][section][slot];
    const write = (section, slot, next) => {
      const out = normalize(current);
      if ("base" === device) {
        out[section][slot] = next;
      } else {
        out.advanced[device][section][slot] = next;
      }
      onChange(out);
    };
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl7,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement(ButtonGroup, { style: { marginBottom: 12 } }, DEVICES.map((d) => /* @__PURE__ */ wp.element.createElement(
        Button4,
        {
          key: d.key,
          size: "small",
          variant: device === d.key ? "primary" : "secondary",
          onClick: () => setDevice(d.key)
        },
        d.label
      ))),
      sections.map((section) => /* @__PURE__ */ wp.element.createElement("div", { key: section, style: { marginBottom: 12 } }, /* @__PURE__ */ wp.element.createElement("strong", { style: { display: "block", marginBottom: 6, textTransform: "capitalize" } }, section), /* @__PURE__ */ wp.element.createElement(Flex4, { wrap: true, gap: 2, justify: "flex-start" }, SLOTS.map((slot) => /* @__PURE__ */ wp.element.createElement(FlexItem4, { key: slot, style: { minWidth: 92 } }, /* @__PURE__ */ wp.element.createElement(
        SelectControl3,
        {
          label: slot,
          value: read(section, slot),
          options: choices,
          onChange: (next) => write(section, slot, next),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ))))))
    );
  }

  // ../framework/static/js/controls/src/controls/typography.jsx
  var {
    BaseControl: BaseControl8,
    TextControl: TextControl2,
    SelectControl: SelectControl4,
    ColorPicker: ColorPicker2,
    Button: Button5,
    Flex: Flex5,
    FlexItem: FlexItem5,
    __experimentalNumberControl: NumberControl2
  } = wp.components;
  var { useState: useState3 } = wp.element;
  var ALL_COMPONENTS = ["family", "size", "line-height", "letter-spacing", "color", "weight", "style", "variation", "subset"];
  var WEIGHTS = ["100", "200", "300", "400", "500", "600", "700", "800", "900"];
  var STYLES = ["normal", "italic", "oblique"];
  var SIZE_UNITS = ["px", "rem", "em"];
  var COMMON_FAMILIES = [
    "Arial",
    "Helvetica",
    "Georgia",
    "Times New Roman",
    "Courier New",
    "Verdana",
    "Tahoma",
    "Trebuchet MS",
    "Palatino",
    "Garamond"
  ];
  function toPlainHex(raw) {
    const input = String(raw != null ? raw : "").trim();
    if (!input) {
      return "";
    }
    if (/^#([a-f0-9]{3}|[a-f0-9]{6})$/i.test(input)) {
      return input;
    }
    const eight = input.match(/^#([a-f0-9]{6})[a-f0-9]{2}$/i);
    if (eight) {
      return "#" + eight[1];
    }
    const m = input.match(/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)/i);
    if (!m) {
      return "";
    }
    const hx = (n) => Math.max(0, Math.min(255, Math.round(Number(n)))).toString(16).padStart(2, "0");
    return `#${hx(m[1])}${hx(m[2])}${hx(m[3])}`;
  }
  function Typography({ option = {}, value, onChange }) {
    var _a, _b, _c, _d, _e;
    const [pickerOpen, setPickerOpen] = useState3(false);
    const components = Object.assign(
      ALL_COMPONENTS.reduce((acc, k) => Object.assign(acc, { [k]: true }), {}),
      option.components || {}
    );
    const sizeFormat = "number" === option.size_format ? "number" : "unit";
    const current = value && typeof value === "object" ? value : option.value || {};
    const on = (key) => !!components[key];
    const set = (key, next) => onChange(Object.assign({}, current, { [key]: next }));
    const size = current.size;
    const sizeNum = size && typeof size === "object" ? String((_a = size.value) != null ? _a : "") : String(size != null ? size : "");
    const sizeUnit = size && typeof size === "object" ? size.unit || "px" : "px";
    const setSize = (num, unit) => set("size", "unit" === sizeFormat ? { value: String(num != null ? num : ""), unit } : Number(num) || 0);
    const colorValue = "string" === typeof current.color ? current.color : "";
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl8,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      on("family") && /* @__PURE__ */ wp.element.createElement(wp.element.Fragment, null, /* @__PURE__ */ wp.element.createElement(
        TextControl2,
        {
          label: "Font family",
          value: "string" === typeof current.family ? current.family : "",
          list: "fw-typography-families",
          onChange: (next) => set("family", next),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ), /* @__PURE__ */ wp.element.createElement("datalist", { id: "fw-typography-families" }, COMMON_FAMILIES.map((f) => /* @__PURE__ */ wp.element.createElement("option", { key: f, value: f })))),
      on("size") && /* @__PURE__ */ wp.element.createElement(Flex5, { align: "flex-end", gap: 2, style: { marginTop: 8 } }, /* @__PURE__ */ wp.element.createElement(FlexItem5, { isBlock: true }, /* @__PURE__ */ wp.element.createElement(
        NumberControl2,
        {
          label: "Size",
          value: sizeNum,
          onChange: (next) => setSize(next, sizeUnit),
          __next40pxDefaultSize: true
        }
      )), "unit" === sizeFormat && /* @__PURE__ */ wp.element.createElement(FlexItem5, null, /* @__PURE__ */ wp.element.createElement(
        SelectControl4,
        {
          value: sizeUnit,
          options: SIZE_UNITS.map((u) => ({ value: u, label: u })),
          onChange: (next) => setSize(sizeNum, next),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ))),
      on("line-height") && /* @__PURE__ */ wp.element.createElement(
        NumberControl2,
        {
          label: "Line height",
          value: String((_b = current["line-height"]) != null ? _b : ""),
          onChange: (next) => set("line-height", Number(next) || 0),
          __next40pxDefaultSize: true
        }
      ),
      on("letter-spacing") && /* @__PURE__ */ wp.element.createElement(
        NumberControl2,
        {
          label: "Letter spacing",
          value: String((_c = current["letter-spacing"]) != null ? _c : ""),
          onChange: (next) => set("letter-spacing", Number(next) || 0),
          __next40pxDefaultSize: true
        }
      ),
      on("weight") && false !== current.weight && /* @__PURE__ */ wp.element.createElement(
        SelectControl4,
        {
          label: "Weight",
          value: String((_d = current.weight) != null ? _d : "400"),
          options: WEIGHTS.map((w) => ({ value: w, label: w })),
          onChange: (next) => set("weight", next),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ),
      on("style") && false !== current.style && /* @__PURE__ */ wp.element.createElement(
        SelectControl4,
        {
          label: "Style",
          value: String((_e = current.style) != null ? _e : "normal"),
          options: STYLES.map((s) => ({ value: s, label: s })),
          onChange: (next) => set("style", next),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ),
      on("color") && /* @__PURE__ */ wp.element.createElement("div", { style: { marginTop: 8 } }, /* @__PURE__ */ wp.element.createElement(Flex5, { justify: "flex-start", align: "center", gap: 2 }, /* @__PURE__ */ wp.element.createElement(FlexItem5, null, /* @__PURE__ */ wp.element.createElement(
        Button5,
        {
          variant: "secondary",
          onClick: () => setPickerOpen(!pickerOpen),
          style: {
            background: colorValue || "transparent",
            border: "1px solid #949494",
            minWidth: 40,
            height: 30
          }
        },
        colorValue ? "" : "\u2014"
      )), /* @__PURE__ */ wp.element.createElement(FlexItem5, null, /* @__PURE__ */ wp.element.createElement("code", null, colorValue || "unset"))), pickerOpen && /* @__PURE__ */ wp.element.createElement(
        ColorPicker2,
        {
          color: colorValue || void 0,
          enableAlpha: false,
          onChange: (next) => set("color", toPlainHex(next))
        }
      ))
    );
  }

  // ../framework/static/js/controls/src/controls/icon.jsx
  var { BaseControl: BaseControl9, SelectControl: SelectControl5, TextControl: TextControl3, Button: Button6, Flex: Flex6, FlexItem: FlexItem6, Notice: Notice2 } = wp.components;
  function getMediaUpload2() {
    if (wp.mediaUtils && wp.mediaUtils.MediaUpload) {
      return wp.mediaUtils.MediaUpload;
    }
    if (wp.blockEditor && wp.blockEditor.MediaUpload) {
      return wp.blockEditor.MediaUpload;
    }
    return null;
  }
  function normalize2(value) {
    if ("string" === typeof value) {
      return "" === value ? { type: "none" } : { type: "icon-font", "icon-class": value };
    }
    if (!value || "object" !== typeof value || !value.type) {
      return { type: "none" };
    }
    return value;
  }
  var EDITABLE_TYPES = [
    { value: "none", label: "None" },
    { value: "icon-font", label: "Icon font" },
    { value: "emoji", label: "Emoji" },
    { value: "custom-upload", label: "Custom image" }
  ];
  function Icon({ option = {}, value, onChange }) {
    const current = normalize2(value);
    const MediaUpload = getMediaUpload2();
    if ("svg" === current.type) {
      return /* @__PURE__ */ wp.element.createElement(BaseControl9, { label: option.label || "", help: option.desc || void 0, __nextHasNoMarginBottom: true }, /* @__PURE__ */ wp.element.createElement(Notice2, { status: "info", isDismissible: false }, "This icon uses an SVG. Edit it in the page builder \u2014 the SVG library, uploads and pasted markup are handled on the server."));
    }
    const setType = (type) => onChange({ type });
    return /* @__PURE__ */ wp.element.createElement(BaseControl9, { label: option.label || "", help: option.desc || void 0, __nextHasNoMarginBottom: true }, /* @__PURE__ */ wp.element.createElement(
      SelectControl5,
      {
        label: "Icon type",
        value: current.type,
        options: EDITABLE_TYPES,
        onChange: setType,
        __next40pxDefaultSize: true,
        __nextHasNoMarginBottom: true
      }
    ), "icon-font" === current.type && /* @__PURE__ */ wp.element.createElement(
      TextControl3,
      {
        label: "Icon class",
        help: "e.g. fa fa-star \u2014 the icon pack browser lives in the page builder.",
        value: current["icon-class"] || "",
        onChange: (next) => onChange({ type: "icon-font", "icon-class": next }),
        __next40pxDefaultSize: true,
        __nextHasNoMarginBottom: true
      }
    ), "emoji" === current.type && /* @__PURE__ */ wp.element.createElement(
      TextControl3,
      {
        label: "Emoji",
        value: current.char || "",
        onChange: (next) => onChange({ type: "emoji", char: next }),
        __next40pxDefaultSize: true,
        __nextHasNoMarginBottom: true
      }
    ), "custom-upload" === current.type && /* @__PURE__ */ wp.element.createElement(Flex6, { align: "center", gap: 2, style: { marginTop: 8 } }, current.url && /* @__PURE__ */ wp.element.createElement(FlexItem6, null, /* @__PURE__ */ wp.element.createElement("img", { src: current.url, alt: "", style: { maxWidth: 48, height: "auto", display: "block" } })), /* @__PURE__ */ wp.element.createElement(FlexItem6, null, MediaUpload ? /* @__PURE__ */ wp.element.createElement(
      MediaUpload,
      {
        allowedTypes: ["image"],
        value: current["attachment-id"] || void 0,
        onSelect: (media) => onChange({
          type: "custom-upload",
          "attachment-id": media.id,
          url: media.url
        }),
        render: ({ open }) => /* @__PURE__ */ wp.element.createElement(Button6, { variant: "secondary", onClick: open }, current.url ? "Replace image" : "Select image")
      }
    ) : /* @__PURE__ */ wp.element.createElement(Notice2, { status: "warning", isDismissible: false }, "The media picker is unavailable on this screen.")), current.url && /* @__PURE__ */ wp.element.createElement(FlexItem6, null, /* @__PURE__ */ wp.element.createElement(Button6, { variant: "tertiary", onClick: () => onChange({ type: "none" }) }, "Remove"))));
  }

  // ../framework/static/js/controls/src/controls/predefined-colors-compact.jsx
  var { BaseControl: BaseControl10, SelectControl: SelectControl6, ColorPicker: ColorPicker3, Button: Button7, Flex: Flex7, FlexItem: FlexItem7 } = wp.components;
  var { useState: useState4 } = wp.element;
  function normalize3(value) {
    if ("string" === typeof value) {
      return { predefined: value, custom: "" };
    }
    if (!value || "object" !== typeof value) {
      return { predefined: "", custom: "" };
    }
    return {
      predefined: "string" === typeof value.predefined ? value.predefined : "",
      custom: "string" === typeof value.custom ? value.custom : ""
    };
  }
  function PredefinedColorsCompact({ option = {}, value, onChange }) {
    const [open, setOpen] = useState4(false);
    const current = normalize3(value);
    const choices = option.choices || {};
    const alpha = "rgba-color-picker" === option.picker;
    const presetOptions = [{ value: "", label: "\u2014 Select \u2014" }].concat(
      Object.keys(choices).map((key) => ({
        value: key,
        label: choices[key] && choices[key].label || key
      }))
    );
    const presetColor = current.predefined && choices[current.predefined] ? choices[current.predefined].color || "" : "";
    const effective = presetColor || current.custom || "";
    const emit = (next) => onChange(Object.assign({}, current, next));
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl10,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement(
        SelectControl6,
        {
          label: "Preset",
          value: current.predefined,
          options: presetOptions,
          onChange: (next) => emit({ predefined: next }),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ),
      /* @__PURE__ */ wp.element.createElement(Flex7, { justify: "flex-start", align: "center", gap: 2, style: { marginTop: 8 } }, /* @__PURE__ */ wp.element.createElement(FlexItem7, null, /* @__PURE__ */ wp.element.createElement(
        Button7,
        {
          variant: "secondary",
          onClick: () => setOpen(!open),
          "aria-expanded": open,
          style: {
            background: effective || "transparent",
            border: "1px solid #949494",
            minWidth: 40,
            height: 30
          }
        },
        effective ? "" : "\u2014"
      )), /* @__PURE__ */ wp.element.createElement(FlexItem7, null, /* @__PURE__ */ wp.element.createElement("code", null, current.custom || (presetColor ? `${current.predefined} (preset)` : "unset"))), current.custom && /* @__PURE__ */ wp.element.createElement(FlexItem7, null, /* @__PURE__ */ wp.element.createElement(Button7, { variant: "tertiary", onClick: () => emit({ custom: "" }) }, "Clear custom"))),
      open && /* @__PURE__ */ wp.element.createElement(
        ColorPicker3,
        {
          color: current.custom || void 0,
          enableAlpha: alpha,
          onChange: (next) => emit({ custom: String(next != null ? next : "") })
        }
      )
    );
  }

  // ../framework/static/js/controls/src/controls/wp-editor.jsx
  var { TextareaControl: TextareaControl2, BaseControl: BaseControl11 } = wp.components;
  function WpEditor({ option = {}, value = "", onChange }) {
    const autop = false !== option.wpautop;
    const help = option.desc || (autop ? "HTML is allowed. Plain text is wrapped in paragraphs when saved." : "HTML is allowed and stored exactly as written.");
    return /* @__PURE__ */ wp.element.createElement(BaseControl11, { __nextHasNoMarginBottom: true }, /* @__PURE__ */ wp.element.createElement(
      TextareaControl2,
      {
        label: option.label || "",
        help,
        value: "string" === typeof value ? value : "",
        rows: option.editor_height ? Math.max(4, Math.round(option.editor_height / 24)) : 6,
        onChange,
        __nextHasNoMarginBottom: true
      }
    ));
  }

  // ../framework/static/js/controls/src/controls/border-style-picker.jsx
  var { SelectControl: SelectControl7, BaseControl: BaseControl12, Flex: Flex8, FlexItem: FlexItem8 } = wp.components;
  function BorderStylePicker({ option = {}, value, onChange }) {
    var _a;
    const choices = option.choices || {};
    const allowNone = false !== option.allow_none;
    const options = (allowNone ? [{ value: "", label: option.placeholder || "\u2014 Select \u2014" }] : []).concat(
      Object.keys(choices).map((key) => {
        const choice = choices[key];
        return {
          value: key,
          label: "string" === typeof choice ? choice : choice && (choice.label || choice.text) || key
        };
      })
    );
    const current = (_a = value != null ? value : option.value) != null ? _a : "";
    const previews = "badge" === option.preview_kind && option.previews || {};
    const swatch = previews[current] && previews[current].tile_style;
    const select = /* @__PURE__ */ wp.element.createElement(
      SelectControl7,
      {
        label: option.label || "",
        help: option.desc || void 0,
        value: String(current),
        options,
        onChange,
        __next40pxDefaultSize: true,
        __nextHasNoMarginBottom: true
      }
    );
    if (!swatch) {
      return select;
    }
    return /* @__PURE__ */ wp.element.createElement(BaseControl12, { __nextHasNoMarginBottom: true }, /* @__PURE__ */ wp.element.createElement(Flex8, { align: "flex-end", gap: 2 }, /* @__PURE__ */ wp.element.createElement(FlexItem8, { isBlock: true }, select), /* @__PURE__ */ wp.element.createElement(FlexItem8, null, /* @__PURE__ */ wp.element.createElement(
      "div",
      {
        "aria-hidden": "true",
        style: String(swatch).split(";").reduce((acc, decl) => {
          const [prop, val] = decl.split(":");
          if (!prop || !val) {
            return acc;
          }
          const key = prop.trim().replace(/-([a-z])/g, (_, c) => c.toUpperCase());
          return Object.assign(acc, { [key]: val.trim() });
        }, { width: 34, height: 26 })
      }
    ))));
  }

  // ../framework/static/js/controls/src/index.jsx
  var { Notice: Notice3 } = wp.components;
  register("text", Text);
  register("switch", Switch);
  register("select", Select);
  register("short-select", Select);
  register("upload", Upload);
  register("textarea", Textarea);
  register("radio", Radio);
  register("checkbox", Checkbox);
  register("color-picker", ColorPickerControl);
  register("slider", Slider);
  register("short-slider", Slider);
  register("unit-input", UnitInput);
  register("multi-select", MultiSelect);
  register("image-picker", ImagePicker);
  register("spacing", Spacing);
  register("typography", Typography);
  register("typography-v2", Typography);
  register("icon", Icon);
  register("predefined-colors-color-picker-compact", PredefinedColorsCompact);
  register("wp-editor", WpEditor);
  register("border-style-picker", BorderStylePicker);
  function Undefined({ type }) {
    return /* @__PURE__ */ wp.element.createElement(Notice3, { status: "warning", isDismissible: false }, `No React control for option type "${type}" yet \u2014 edit this option in the page builder.`);
  }
  function Option({ option, value, onChange }) {
    const type = option && option.type;
    if (!type) {
      return null;
    }
    const Control = get(type);
    if (!Control) {
      return /* @__PURE__ */ wp.element.createElement(Undefined, { type });
    }
    return /* @__PURE__ */ wp.element.createElement(Control, { option, value, onChange });
  }
  function Options({ options = {}, values = {}, onChange }) {
    return /* @__PURE__ */ wp.element.createElement(wp.element.Fragment, null, Object.keys(options).map((id) => /* @__PURE__ */ wp.element.createElement(
      Option,
      {
        key: id,
        option: options[id],
        value: values[id],
        onChange: (next) => onChange(id, next)
      }
    )));
  }
  window.fw = window.fw || {};
  window.fw.controls = { register, get, has, types, Option, Options };
})();
//# sourceMappingURL=fw-controls.js.map
