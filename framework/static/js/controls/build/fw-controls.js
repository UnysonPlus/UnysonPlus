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
    const MediaUpload2 = getMediaUpload();
    const current = value && value.url ? value : null;
    if (!MediaUpload2) {
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
        MediaUpload2,
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
    const MediaUpload2 = getMediaUpload2();
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
    ), "custom-upload" === current.type && /* @__PURE__ */ wp.element.createElement(Flex6, { align: "center", gap: 2, style: { marginTop: 8 } }, current.url && /* @__PURE__ */ wp.element.createElement(FlexItem6, null, /* @__PURE__ */ wp.element.createElement("img", { src: current.url, alt: "", style: { maxWidth: 48, height: "auto", display: "block" } })), /* @__PURE__ */ wp.element.createElement(FlexItem6, null, MediaUpload2 ? /* @__PURE__ */ wp.element.createElement(
      MediaUpload2,
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

  // ../framework/static/js/controls/src/controls/number.jsx
  var { TextControl: TextControl4 } = wp.components;
  var { useState: useState5, useEffect } = wp.element;
  function cast(raw, option) {
    const n = parseFloat(raw);
    if (!Number.isFinite(n)) {
      return option.numeric_type === "integer" ? 0 : 0;
    }
    return option.numeric_type === "integer" ? Math.trunc(n) : n;
  }
  function clamp(value, option) {
    const { min, max } = option;
    if (min !== null && min !== void 0 && min !== "" && value < Number(min)) {
      return cast(min, option);
    }
    if (max !== null && max !== void 0 && max !== "" && value > Number(max)) {
      return cast(max, option);
    }
    return value;
  }
  function Number_({ option = {}, value, onChange }) {
    var _a, _b, _c, _d;
    const stored = (_a = value != null ? value : option.value) != null ? _a : 0;
    const [draft, setDraft] = useState5(String(stored));
    useEffect(() => {
      setDraft(
        (current) => cast(current, option) === cast(stored, option) ? current : String(stored)
      );
    }, [stored]);
    return /* @__PURE__ */ wp.element.createElement(
      TextControl4,
      {
        type: "number",
        label: option.label || "",
        help: option.desc || void 0,
        value: draft,
        min: (_b = option.min) != null ? _b : void 0,
        max: (_c = option.max) != null ? _c : void 0,
        step: (_d = option.step) != null ? _d : void 0,
        onChange: (next) => {
          setDraft(next);
          if (next !== "" && Number.isFinite(parseFloat(next))) {
            onChange(cast(next, option));
          }
        },
        onBlur: () => {
          var _a2;
          const next = clamp(cast(draft === "" ? (_a2 = option.value) != null ? _a2 : 0 : draft, option), option);
          setDraft(String(next));
          onChange(next);
        },
        __next40pxDefaultSize: true,
        __nextHasNoMarginBottom: true
      }
    );
  }

  // ../framework/static/js/controls/src/controls/addable-popup.jsx
  var { Button: Button8, Card, CardBody, Notice: Notice3, BaseControl: BaseControl13 } = wp.components;
  var { useState: useState6, useMemo } = wp.element;
  function flatten(options) {
    const out = [];
    Object.keys(options || {}).forEach((id) => {
      const option = options[id];
      if (!option || typeof option !== "object") {
        return;
      }
      if (option.options) {
        out.push(...flatten(option.options));
        return;
      }
      if (option.type) {
        out.push([id, option]);
      }
    });
    return out;
  }
  function compileTemplate(template) {
    if (!template || typeof template !== "string") {
      return null;
    }
    const parts = [];
    const pattern = /\{\{=?([\s\S]+?)\}\}/g;
    let last = 0;
    let match;
    while ((match = pattern.exec(template)) !== null) {
      if (match.index > last) {
        parts.push(JSON.stringify(template.slice(last, match.index)));
      }
      parts.push("(" + match[1] + ")");
      last = pattern.lastIndex;
    }
    if (last < template.length) {
      parts.push(JSON.stringify(template.slice(last)));
    }
    if (!parts.length) {
      return null;
    }
    try {
      const fn = new Function(
        "item",
        "with ( item ) { return [" + parts.join(",") + '].join( "" ); }'
      );
      return (item) => {
        try {
          return String(fn(item || {}));
        } catch (e) {
          return "";
        }
      };
    } catch (e) {
      return null;
    }
  }
  function blankItem(fields) {
    const item = {};
    fields.forEach(([id, option]) => {
      item[id] = option.value !== void 0 ? option.value : "";
    });
    return item;
  }
  function Field({ option, value, onChange }) {
    const Control = get(option.type);
    if (!Control) {
      return /* @__PURE__ */ wp.element.createElement(Notice3, { status: "warning", isDismissible: false }, `No React control for "${option.type}" \u2014 edit this item in the page builder.`);
    }
    return /* @__PURE__ */ wp.element.createElement(Control, { option, value, onChange });
  }
  function AddablePopup({ option = {}, value, onChange }) {
    const [openIndex, setOpenIndex] = useState6(null);
    const fields = useMemo(
      () => flatten(option["popup-options"]),
      [option["popup-options"]]
    );
    const label = useMemo(() => compileTemplate(option.template), [option.template]);
    const items = Array.isArray(value) ? value : value && typeof value === "object" ? Object.values(value) : [];
    const limit = parseInt(option.limit, 10) || 0;
    const atLimit = limit > 0 && items.length >= limit;
    const replace = (next) => onChange(next);
    const update = (index, id, next) => replace(
      items.map((item, i) => i === index ? { ...item, [id]: next } : item)
    );
    const move = (index, delta) => {
      const target = index + delta;
      if (target < 0 || target >= items.length) {
        return;
      }
      const next = items.slice();
      [next[index], next[target]] = [next[target], next[index]];
      replace(next);
      setOpenIndex(openIndex === index ? target : openIndex);
    };
    const remove = (index) => {
      replace(items.filter((_item, i) => i !== index));
      setOpenIndex(null);
    };
    const duplicate = (index) => {
      if (atLimit) {
        return;
      }
      const next = items.slice();
      next.splice(index + 1, 0, { ...items[index] });
      replace(next);
    };
    const add = () => {
      replace([...items, blankItem(fields)]);
      setOpenIndex(items.length);
    };
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl13,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement("div", { className: "fw-addable-popup" }, items.map((item, index) => {
        const text = label && label(item).trim() || `Item ${index + 1}`;
        const isOpen = openIndex === index;
        return /* @__PURE__ */ wp.element.createElement(Card, { key: index, size: "small", style: { marginBottom: "8px" } }, /* @__PURE__ */ wp.element.createElement(CardBody, { style: { padding: "8px" } }, /* @__PURE__ */ wp.element.createElement(
          "div",
          {
            style: {
              display: "flex",
              alignItems: "center",
              gap: "2px"
            }
          },
          /* @__PURE__ */ wp.element.createElement(
            Button8,
            {
              variant: "tertiary",
              onClick: () => setOpenIndex(isOpen ? null : index),
              style: {
                flex: "1 1 auto",
                justifyContent: "flex-start",
                minWidth: 0,
                overflow: "hidden",
                textOverflow: "ellipsis"
              },
              "aria-expanded": isOpen
            },
            text
          ),
          /* @__PURE__ */ wp.element.createElement(
            Button8,
            {
              icon: "arrow-up-alt2",
              size: "small",
              label: "Move up",
              disabled: index === 0,
              onClick: () => move(index, -1)
            }
          ),
          /* @__PURE__ */ wp.element.createElement(
            Button8,
            {
              icon: "arrow-down-alt2",
              size: "small",
              label: "Move down",
              disabled: index === items.length - 1,
              onClick: () => move(index, 1)
            }
          ),
          /* @__PURE__ */ wp.element.createElement(
            Button8,
            {
              icon: "admin-page",
              size: "small",
              label: "Duplicate",
              disabled: atLimit,
              onClick: () => duplicate(index)
            }
          ),
          /* @__PURE__ */ wp.element.createElement(
            Button8,
            {
              icon: "trash",
              size: "small",
              isDestructive: true,
              label: "Remove",
              onClick: () => remove(index)
            }
          )
        ), isOpen && /* @__PURE__ */ wp.element.createElement("div", { style: { marginTop: "12px" } }, fields.map(([id, sub]) => /* @__PURE__ */ wp.element.createElement(
          Field,
          {
            key: id,
            option: sub,
            value: item ? item[id] : void 0,
            onChange: (next) => update(index, id, next)
          }
        )))));
      }), /* @__PURE__ */ wp.element.createElement(
        Button8,
        {
          variant: "secondary",
          onClick: add,
          disabled: atLimit,
          __next40pxDefaultSize: true
        },
        option["add-button-text"] || "Add"
      ), atLimit && /* @__PURE__ */ wp.element.createElement("p", { style: { margin: "8px 0 0", fontStyle: "italic" } }, `Limit of ${limit} reached.`))
    );
  }

  // ../framework/static/js/controls/src/controls/multi-picker.jsx
  var { Notice: Notice4, BaseControl: BaseControl14 } = wp.components;
  var { useMemo: useMemo2 } = wp.element;
  function prepareChoices(choices) {
    const result = {};
    Object.keys(choices || {}).forEach((key) => {
      const settings = choices[key];
      if (settings && settings.for && settings.options) {
        const targets = Array.isArray(settings.for) ? settings.for : [settings.for];
        const before = (settings.location || "before") === "before";
        targets.forEach((name) => {
          const existing = result[name] || choices[name] || {};
          result[name] = before ? { ...settings.options, ...existing } : { ...existing, ...settings.options };
        });
        return;
      }
      if (result[key] === void 0) {
        result[key] = settings;
      }
    });
    return result;
  }
  function flatten2(options) {
    const out = [];
    Object.keys(options || {}).forEach((id) => {
      const option = options[id];
      if (!option || typeof option !== "object") {
        return;
      }
      if (option.options) {
        out.push(...flatten2(option.options));
        return;
      }
      if (option.type) {
        out.push([id, option]);
      }
    });
    return out;
  }
  function Field2({ option, value, onChange }) {
    const Control = get(option.type);
    if (!Control) {
      return /* @__PURE__ */ wp.element.createElement(Notice4, { status: "warning", isDismissible: false }, `No React control for "${option.type}" yet \u2014 edit this in the page builder.`);
    }
    return /* @__PURE__ */ wp.element.createElement(Control, { option, value, onChange });
  }
  function MultiPicker({ option = {}, value, onChange }) {
    var _a;
    const picker = option.picker || {};
    const pickerKey = Object.keys(picker)[0];
    const pickerOption = pickerKey ? picker[pickerKey] : null;
    const choices = useMemo2(() => prepareChoices(option.choices), [option.choices]);
    if (!pickerOption) {
      return /* @__PURE__ */ wp.element.createElement(Notice4, { status: "error", isDismissible: false }, "This option is missing its picker and cannot be edited here.");
    }
    const current = value && typeof value === "object" ? value : {};
    const selected = (_a = current[pickerKey]) != null ? _a : pickerOption.value;
    const revealed = choices[selected] ? flatten2(choices[selected]) : [];
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl14,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      !option.hide_picker && /* @__PURE__ */ wp.element.createElement(
        Field2,
        {
          option: pickerOption,
          value: selected,
          onChange: (next) => onChange({ ...current, [pickerKey]: next })
        }
      ),
      revealed.length > 0 && /* @__PURE__ */ wp.element.createElement(
        "div",
        {
          style: {
            marginTop: "12px",
            paddingLeft: "12px",
            borderLeft: "2px solid #ddd"
          }
        },
        revealed.map(([id, sub]) => /* @__PURE__ */ wp.element.createElement(
          Field2,
          {
            key: id,
            option: sub,
            value: current[selected] ? current[selected][id] : void 0,
            onChange: (next) => onChange({
              ...current,
              [selected]: { ...current[selected] || {}, [id]: next }
            })
          }
        ))
      )
    );
  }

  // ../framework/static/js/controls/src/controls/image-style-picker.jsx
  var { BaseControl: BaseControl15, Button: Button9 } = wp.components;
  var SAMPLE = `data:image/svg+xml,${encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 60" preserveAspectRatio="xMidYMid slice"><defs><linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#79add9"/><stop offset="1" stop-color="#f3d2a6"/></linearGradient></defs><rect width="80" height="60" fill="url(#sky)"/><circle cx="57" cy="19" r="9" fill="#ffd769"/><path d="M0 43 Q20 30 40 41 T80 39 V60 H0 Z" fill="#3f8f7d"/><path d="M0 51 Q26 40 52 49 T80 49 V60 H0 Z" fill="#2b6a62"/></svg>'
  )}`;
  function ImageStylePicker({ option = {}, value, onChange }) {
    const choices = option.choices && typeof option.choices === "object" ? option.choices : {};
    const allowNone = option.allow_none === void 0 || option.allow_none;
    const current = typeof value === "string" && choices[value] !== void 0 ? value : "";
    const keys = Object.keys(choices).filter((key) => key !== "");
    const tile = (key, label, selected) => /* @__PURE__ */ wp.element.createElement(
      Button9,
      {
        key: key || "__none",
        onClick: () => onChange(key),
        label,
        showTooltip: true,
        style: {
          display: "block",
          height: "auto",
          padding: "4px",
          borderRadius: "4px",
          boxShadow: selected ? "0 0 0 2px var(--wp-admin-theme-color)" : "inset 0 0 0 1px #ddd"
        },
        "aria-pressed": selected
      },
      /* @__PURE__ */ wp.element.createElement(
        "span",
        {
          className: key ? `imgs-wrap ${key}` : void 0,
          style: { display: "block", overflow: "hidden", lineHeight: 0 }
        },
        /* @__PURE__ */ wp.element.createElement(
          "img",
          {
            src: SAMPLE,
            alt: "",
            style: { display: "block", width: "100%", height: "42px", objectFit: "cover" }
          }
        )
      ),
      /* @__PURE__ */ wp.element.createElement(
        "span",
        {
          style: {
            display: "block",
            marginTop: "4px",
            fontSize: "11px",
            lineHeight: 1.3,
            whiteSpace: "normal",
            textAlign: "center"
          }
        },
        label
      )
    );
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl15,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement(
        "div",
        {
          style: {
            display: "grid",
            gridTemplateColumns: "repeat(3, 1fr)",
            gap: "6px"
          }
        },
        allowNone && /* @__PURE__ */ wp.element.createElement(
          Button9,
          {
            onClick: () => onChange(""),
            style: {
              display: "block",
              height: "auto",
              minHeight: "68px",
              padding: "4px",
              borderRadius: "4px",
              fontSize: "11px",
              whiteSpace: "normal",
              boxShadow: current === "" ? "0 0 0 2px var(--wp-admin-theme-color)" : "inset 0 0 0 1px #ddd"
            },
            "aria-pressed": current === ""
          },
          choices[""] || "\u2014 None \u2014"
        ),
        keys.map((key) => tile(key, choices[key] || key, current === key))
      )
    );
  }

  // ../framework/static/js/controls/src/controls/checkboxes.jsx
  var { CheckboxControl: CheckboxControl2, BaseControl: BaseControl16 } = wp.components;
  function Checkboxes({ option = {}, value, onChange }) {
    const choices = option.choices && typeof option.choices === "object" ? option.choices : {};
    const current = value && typeof value === "object" ? value : {};
    const toggle = (key, checked) => {
      const next = {};
      Object.keys(choices).forEach((id) => {
        const on = id === key ? checked : Boolean(current[id]);
        if (on) {
          next[id] = true;
        }
      });
      onChange(next);
    };
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl16,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement(
        "div",
        {
          style: option.inline ? { display: "flex", flexWrap: "wrap", gap: "4px 16px" } : void 0
        },
        Object.keys(choices).map((key) => /* @__PURE__ */ wp.element.createElement(
          CheckboxControl2,
          {
            key,
            label: choices[key],
            checked: Boolean(current[key]),
            onChange: (checked) => toggle(key, checked),
            __nextHasNoMarginBottom: true
          }
        ))
      )
    );
  }

  // ../framework/static/js/controls/src/controls/button-style-picker.jsx
  var { BaseControl: BaseControl17, Button: Button10 } = wp.components;
  function ButtonStylePicker({ option = {}, value, onChange }) {
    const choices = option.choices && typeof option.choices === "object" ? option.choices : {};
    const allowNone = option.allow_none === void 0 || option.allow_none;
    const base = option.preview_base || "btn";
    const text = option.preview_text || "Button";
    const current = typeof value === "string" && choices[value] !== void 0 ? value : "";
    const row = (key, label, selected) => /* @__PURE__ */ wp.element.createElement(
      Button10,
      {
        key: key || "__none",
        onClick: () => onChange(key),
        "aria-pressed": selected,
        style: {
          display: "flex",
          alignItems: "center",
          gap: "10px",
          width: "100%",
          height: "auto",
          padding: "6px 8px",
          marginBottom: "4px",
          borderRadius: "4px",
          textAlign: "left",
          boxShadow: selected ? "0 0 0 2px var(--wp-admin-theme-color)" : "inset 0 0 0 1px #ddd"
        }
      },
      key ? (
        // The preview is decorative — the real control is the row itself, and a
        // nested interactive element would be a second tab stop that does nothing.
        /* @__PURE__ */ wp.element.createElement("span", { className: `${base} ${key}`, "aria-hidden": "true" }, text)
      ) : /* @__PURE__ */ wp.element.createElement("span", { style: { fontStyle: "italic", opacity: 0.7 } }, option.placeholder || "\u2014 Select \u2014"),
      /* @__PURE__ */ wp.element.createElement("span", { style: { fontSize: "11px", opacity: 0.75 } }, label)
    );
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl17,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement("div", null, allowNone && row("", choices[""] || "", current === ""), Object.keys(choices).filter((key) => key !== "").map((key) => row(key, choices[key] || key, current === key)))
    );
  }

  // ../framework/static/js/controls/src/controls/button-hover-animation.jsx
  var { BaseControl: BaseControl18, Button: Button11 } = wp.components;
  function ButtonHoverAnimation({ option = {}, value, onChange }) {
    const choices = option.choices && typeof option.choices === "object" ? option.choices : {};
    const base = option.preview_base || "btn btn-primary";
    const fxCss = option.fx_css || "";
    const current = typeof value === "string" && choices[value] !== void 0 ? value : "";
    const row = (key, label, selected) => /* @__PURE__ */ wp.element.createElement(
      Button11,
      {
        key: key || "__none",
        onClick: () => onChange(key),
        "aria-pressed": selected,
        style: {
          display: "flex",
          alignItems: "center",
          gap: "10px",
          width: "100%",
          height: "auto",
          padding: "6px 8px",
          marginBottom: "4px",
          borderRadius: "4px",
          textAlign: "left",
          boxShadow: selected ? "0 0 0 2px var(--wp-admin-theme-color)" : "inset 0 0 0 1px #ddd"
        }
      },
      key ? /* @__PURE__ */ wp.element.createElement("span", { className: `${base} ${key}`, "aria-hidden": "true" }, "Button") : /* @__PURE__ */ wp.element.createElement("span", { style: { fontStyle: "italic", opacity: 0.7 } }, option.placeholder || "None"),
      /* @__PURE__ */ wp.element.createElement("span", { style: { fontSize: "11px", opacity: 0.75 } }, label)
    );
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl18,
      {
        label: option.label || "",
        help: option.desc || "Hover a row to see the effect.",
        __nextHasNoMarginBottom: true
      },
      fxCss && /* @__PURE__ */ wp.element.createElement("link", { rel: "stylesheet", href: fxCss }),
      /* @__PURE__ */ wp.element.createElement("div", null, row("", choices[""] || "", current === ""), Object.keys(choices).filter((key) => key !== "").map((key) => row(key, choices[key] || key, current === key)))
    );
  }

  // ../framework/static/js/controls/src/controls/column-split.jsx
  var { BaseControl: BaseControl19, Button: Button12 } = wp.components;
  var { useMemo: useMemo3 } = wp.element;
  function gcd(a, b) {
    return b ? gcd(b, a % b) : a;
  }
  function parseFraction(raw) {
    const m = String(raw != null ? raw : "").match(/^\s*(\d+)\s*\/\s*(\d+)\s*$/);
    if (!m) {
      return null;
    }
    const n = parseInt(m[1], 10);
    const d = parseInt(m[2], 10);
    if (!(d > 0 && n > 0 && n < d)) {
      return null;
    }
    const g = gcd(n, d);
    return [n / g, d / g];
  }
  function allowedFractions(option) {
    const raw = Array.isArray(option.fractions) && option.fractions.length ? option.fractions : Array.from({ length: 11 }, (_v, i) => `${i + 1}/12`);
    const seen = /* @__PURE__ */ new Map();
    raw.forEach((f) => {
      const r = parseFraction(f);
      if (r) {
        seen.set(`${r[0]}/${r[1]}`, r[0] / r[1]);
      }
    });
    return [...seen.entries()].sort((a, b) => a[1] - b[1]).map((e) => e[0]);
  }
  function ColumnSplit({ option = {}, value, onChange }) {
    const allowed = useMemo3(() => allowedFractions(option), [option.fractions]);
    const reduced = parseFraction(value);
    const key = reduced ? `${reduced[0]}/${reduced[1]}` : null;
    const current = key && allowed.includes(key) ? key : option.value || "1/2";
    const panes = Array.isArray(option.panes) ? option.panes : [];
    const leftLabel = panes[0] && panes[0].label ? panes[0].label : "Left";
    const rightLabel = panes[1] && panes[1].label ? panes[1].label : "Right";
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl19,
      {
        label: option.label || "",
        help: option.desc || `${leftLabel} / ${rightLabel}`,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement("div", { style: { display: "flex", flexWrap: "wrap", gap: "4px" } }, allowed.map((frac) => {
        const [n, d] = frac.split("/").map(Number);
        const pct = n / d * 100;
        const selected = frac === current;
        return /* @__PURE__ */ wp.element.createElement(
          Button12,
          {
            key: frac,
            onClick: () => onChange(frac),
            "aria-pressed": selected,
            label: `${leftLabel} ${frac}`,
            showTooltip: true,
            style: {
              display: "block",
              height: "auto",
              padding: "4px",
              borderRadius: "4px",
              boxShadow: selected ? "0 0 0 2px var(--wp-admin-theme-color)" : "inset 0 0 0 1px #ddd"
            }
          },
          /* @__PURE__ */ wp.element.createElement(
            "span",
            {
              style: {
                display: "flex",
                gap: "2px",
                width: "46px",
                height: "14px"
              },
              "aria-hidden": "true"
            },
            /* @__PURE__ */ wp.element.createElement(
              "span",
              {
                style: {
                  width: `${pct}%`,
                  background: "var(--wp-admin-theme-color)",
                  borderRadius: "2px",
                  opacity: 0.85
                }
              }
            ),
            /* @__PURE__ */ wp.element.createElement(
              "span",
              {
                style: {
                  width: `${100 - pct}%`,
                  background: "#c3c4c7",
                  borderRadius: "2px"
                }
              }
            )
          ),
          option.show_fraction !== false && /* @__PURE__ */ wp.element.createElement(
            "span",
            {
              style: {
                display: "block",
                marginTop: "2px",
                fontSize: "10px",
                lineHeight: 1.2,
                textAlign: "center"
              }
            },
            frac
          )
        );
      }))
    );
  }

  // ../framework/static/js/controls/src/controls/code-editor.jsx
  var { TextareaControl: TextareaControl3 } = wp.components;
  function CodeEditor({ option = {}, value = "", onChange }) {
    const height = Math.max(120, Math.min(400, parseInt(option.height, 10) || 300));
    const help = option.desc ? option.desc : option.mode ? `${option.mode} \u2014 edited as plain text here; use the page builder for syntax highlighting.` : void 0;
    return /* @__PURE__ */ wp.element.createElement(
      TextareaControl3,
      {
        label: option.label || "",
        help,
        value: value != null ? value : "",
        placeholder: option.placeholder || void 0,
        onChange,
        rows: Math.round(height / 20),
        spellCheck: false,
        autoCapitalize: "off",
        autoCorrect: "off",
        autoComplete: "off",
        style: { fontFamily: "Menlo, Consolas, monospace", fontSize: "12px" },
        __nextHasNoMarginBottom: true
      }
    );
  }

  // ../framework/static/js/controls/src/controls/datetime-picker.jsx
  var { TextControl: TextControl5, BaseControl: BaseControl20 } = wp.components;
  var SUPPORTED = "YymndjHGis";
  var pad = (n) => String(n).padStart(2, "0");
  function formatWith(date, format) {
    var _a;
    let out = "";
    for (let i = 0; i < format.length; i++) {
      const ch = format[i];
      if (ch === "\\") {
        out += (_a = format[++i]) != null ? _a : "";
        continue;
      }
      switch (ch) {
        case "Y":
          out += date.getFullYear();
          break;
        case "y":
          out += pad(date.getFullYear() % 100);
          break;
        case "m":
          out += pad(date.getMonth() + 1);
          break;
        case "n":
          out += date.getMonth() + 1;
          break;
        case "d":
          out += pad(date.getDate());
          break;
        case "j":
          out += date.getDate();
          break;
        case "H":
          out += pad(date.getHours());
          break;
        case "G":
          out += date.getHours();
          break;
        case "i":
          out += pad(date.getMinutes());
          break;
        case "s":
          out += pad(date.getSeconds());
          break;
        default:
          out += ch;
      }
    }
    return out;
  }
  function isSupported(format) {
    for (let i = 0; i < format.length; i++) {
      if (format[i] === "\\") {
        i++;
        continue;
      }
      if (/[A-Za-z]/.test(format[i]) && !SUPPORTED.includes(format[i])) {
        return false;
      }
    }
    return true;
  }
  function toInputValue(stored, withTime) {
    const m = String(stored != null ? stored : "").match(
      /^(\d{4})\D(\d{1,2})\D(\d{1,2})(?:\D+(\d{1,2}):(\d{2}))?/
    );
    if (!m) {
      const t = String(stored != null ? stored : "").match(/^(\d{1,2}):(\d{2})$/);
      return t ? `${pad(t[1])}:${t[2]}` : "";
    }
    const date = `${m[1]}-${pad(m[2])}-${pad(m[3])}`;
    return withTime ? `${date}T${pad(m[4] || 0)}:${m[5] || "00"}` : date;
  }
  function DatetimePicker({ option = {}, value, onChange }) {
    const config = option["datetime-picker"] || {};
    const format = config.format || "Y/m/d H:i";
    const hasDate = config.datepicker !== false;
    const hasTime = config.timepicker !== false;
    if (!isSupported(format)) {
      return /* @__PURE__ */ wp.element.createElement(
        TextControl5,
        {
          label: option.label || "",
          help: `${option.desc ? option.desc + " " : ""}Format: ${format}`,
          value: value != null ? value : "",
          onChange,
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      );
    }
    const type = hasDate && hasTime ? "datetime-local" : hasDate ? "date" : "time";
    const handle = (next) => {
      if (!next) {
        onChange("");
        return;
      }
      const parsed = type === "time" ? /* @__PURE__ */ new Date(`1970-01-01T${next}`) : new Date(next);
      onChange(Number.isNaN(parsed.getTime()) ? "" : formatWith(parsed, format));
    };
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl20,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement(
        "input",
        {
          type,
          value: toInputValue(value, hasDate && hasTime),
          min: config.minDate || void 0,
          max: config.maxDate || void 0,
          onChange: (e) => handle(e.target.value),
          style: {
            width: "100%",
            padding: "6px 8px",
            border: "1px solid #949494",
            borderRadius: "2px"
          }
        }
      )
    );
  }

  // ../framework/static/js/controls/src/controls/multi-upload.jsx
  var { BaseControl: BaseControl21, Button: Button13 } = wp.components;
  var { MediaUpload, MediaUploadCheck } = wp.blockEditor;
  function toStored(media) {
    return {
      attachment_id: media.id,
      url: String(media.url || "").replace(/^https?:\/\//, "//")
    };
  }
  function MultiUpload({ option = {}, value, onChange }) {
    const items = Array.isArray(value) ? value : [];
    const ids = items.map((item) => item && item.attachment_id ? parseInt(item.attachment_id, 10) : null).filter(Boolean);
    const remove = (index) => onChange(items.filter((_item, i) => i !== index));
    const move = (index, delta) => {
      const target = index + delta;
      if (target < 0 || target >= items.length) {
        return;
      }
      const next = items.slice();
      [next[index], next[target]] = [next[target], next[index]];
      onChange(next);
    };
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl21,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      items.length > 0 && /* @__PURE__ */ wp.element.createElement(
        "div",
        {
          style: {
            display: "grid",
            gridTemplateColumns: "repeat(3, 1fr)",
            gap: "6px",
            marginBottom: "8px"
          }
        },
        items.map((item, index) => /* @__PURE__ */ wp.element.createElement(
          "div",
          {
            key: `${item.attachment_id}-${index}`,
            style: {
              position: "relative",
              border: "1px solid #ddd",
              borderRadius: "4px",
              overflow: "hidden"
            }
          },
          /* @__PURE__ */ wp.element.createElement(
            "img",
            {
              src: item.url,
              alt: "",
              style: {
                display: "block",
                width: "100%",
                height: "54px",
                objectFit: "cover"
              }
            }
          ),
          /* @__PURE__ */ wp.element.createElement(
            "div",
            {
              style: {
                display: "flex",
                justifyContent: "center",
                gap: "1px",
                padding: "2px 0"
              }
            },
            /* @__PURE__ */ wp.element.createElement(
              Button13,
              {
                icon: "arrow-left-alt2",
                size: "small",
                label: "Move earlier",
                disabled: index === 0,
                onClick: () => move(index, -1)
              }
            ),
            /* @__PURE__ */ wp.element.createElement(
              Button13,
              {
                icon: "arrow-right-alt2",
                size: "small",
                label: "Move later",
                disabled: index === items.length - 1,
                onClick: () => move(index, 1)
              }
            ),
            /* @__PURE__ */ wp.element.createElement(
              Button13,
              {
                icon: "trash",
                size: "small",
                isDestructive: true,
                label: "Remove",
                onClick: () => remove(index)
              }
            )
          )
        ))
      ),
      /* @__PURE__ */ wp.element.createElement(MediaUploadCheck, null, /* @__PURE__ */ wp.element.createElement(
        MediaUpload,
        {
          multiple: true,
          gallery: true,
          addToGallery: true,
          allowedTypes: option.images_only === false ? void 0 : ["image"],
          value: ids,
          onSelect: (media) => onChange((Array.isArray(media) ? media : [media]).map(toStored)),
          render: ({ open }) => /* @__PURE__ */ wp.element.createElement(Button13, { variant: "secondary", onClick: open, __next40pxDefaultSize: true }, items.length ? "Edit selection" : "Add media")
        }
      ))
    );
  }

  // ../framework/static/js/controls/src/controls/addable-box.jsx
  function AddableBox({ option = {}, value, onChange }) {
    const remapped = {
      ...option,
      "popup-options": option["box-options"] || option["popup-options"] || {}
    };
    return /* @__PURE__ */ wp.element.createElement(AddablePopup, { option: remapped, value, onChange });
  }

  // ../framework/static/js/controls/src/controls/date-picker.jsx
  var { BaseControl: BaseControl22 } = wp.components;
  function toInput(stored) {
    const m = String(stored != null ? stored : "").match(/^(\d{2})-(\d{2})-(\d{4})$/);
    return m ? `${m[3]}-${m[2]}-${m[1]}` : "";
  }
  function toStored2(input) {
    const m = String(input != null ? input : "").match(/^(\d{4})-(\d{2})-(\d{2})$/);
    return m ? `${m[3]}-${m[2]}-${m[1]}` : "";
  }
  function DatePicker({ option = {}, value, onChange }) {
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl22,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement(
        "input",
        {
          type: "date",
          value: toInput(value),
          min: toInput(option["min-date"]) || void 0,
          max: toInput(option["max-date"]) || void 0,
          onChange: (e) => onChange(toStored2(e.target.value)),
          style: {
            width: "100%",
            padding: "6px 8px",
            border: "1px solid #949494",
            borderRadius: "2px"
          }
        }
      )
    );
  }

  // ../framework/static/js/controls/src/controls/multi-inline.jsx
  var { BaseControl: BaseControl23, Notice: Notice5 } = wp.components;
  function MultiInline({ option = {}, value, onChange }) {
    const children = option.fw_multi_options && typeof option.fw_multi_options === "object" ? option.fw_multi_options : {};
    const current = value && typeof value === "object" ? value : {};
    const ids = Object.keys(children);
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl23,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement(
        "div",
        {
          style: {
            display: "grid",
            /*
             * `equal` asks for evenly-sized fields, and two or three fit a
             * sidebar. Beyond that they stack: squeezing four text inputs into
             * a 280px column produces fields too narrow to read what is in them.
             */
            gridTemplateColumns: option.equal && ids.length <= 3 ? `repeat(${ids.length}, 1fr)` : "1fr",
            gap: "8px"
          }
        },
        ids.map((id) => {
          const child2 = children[id];
          const Control = get(child2.type);
          if (!Control) {
            return /* @__PURE__ */ wp.element.createElement(Notice5, { key: id, status: "warning", isDismissible: false }, `No React control for "${child2.type}" yet.`);
          }
          return /* @__PURE__ */ wp.element.createElement(
            Control,
            {
              key: id,
              option: { ...child2, label: child2.title || child2.label || id },
              value: current[id],
              onChange: (next) => onChange({ ...current, [id]: next })
            }
          );
        })
      )
    );
  }

  // ../framework/static/js/controls/src/controls/gmap-key.jsx
  var { BaseControl: BaseControl24, Notice: Notice6 } = wp.components;
  function GmapKey({ option = {}, value }) {
    const set = typeof value === "string" && value.trim() !== "";
    return /* @__PURE__ */ wp.element.createElement(BaseControl24, { label: option.label || "Google Maps API key", __nextHasNoMarginBottom: true }, /* @__PURE__ */ wp.element.createElement(Notice6, { status: set ? "success" : "warning", isDismissible: false }, set ? "A site-wide key is set. It is stored for the whole site rather than per block \u2014 change it in the page builder or Theme Settings." : "No site-wide key is set, and a Google map will not load without one. Add it in the page builder or Theme Settings."));
  }

  // ../framework/static/js/controls/src/controls/split-slider.jsx
  var { BaseControl: BaseControl25, Button: Button14, TextControl: TextControl6, ToggleControl: ToggleControl2 } = wp.components;
  function normalize4(segs, option) {
    const minWidth = Math.max(1, parseInt(option.min_width, 10) || 10);
    const n = segs.length;
    if (!n) {
      return [];
    }
    let sum = segs.reduce((acc, s) => acc + Math.max(0, Number(s.w) || 0), 0);
    if (sum <= 0) {
      const each = Math.floor(100 / n);
      return segs.map((s, i) => ({
        ...s,
        w: i === n - 1 ? 100 - each * (n - 1) : each
      }));
    }
    const scaled = segs.map((s) => ({
      ...s,
      w: Math.max(minWidth, Math.round(Math.max(0, Number(s.w) || 0) / sum * 100))
    }));
    const total = scaled.reduce((acc, s) => acc + s.w, 0);
    const drift = 100 - total;
    if (drift !== 0) {
      let widest = 0;
      scaled.forEach((s, i) => {
        if (s.w > scaled[widest].w) {
          widest = i;
        }
      });
      scaled[widest] = {
        ...scaled[widest],
        w: Math.max(minWidth, scaled[widest].w + drift)
      };
    }
    return scaled;
  }
  function SplitSlider({ option = {}, value, onChange }) {
    const segs = Array.isArray(value) ? value : [];
    const isAuto = segs.length === 0;
    const min = Math.max(1, parseInt(option.min, 10) || 1);
    const max = Math.max(min, parseInt(option.max, 10) || 5);
    const autoCount = Math.min(max, Math.max(min, parseInt(option.auto_count, 10) || 3));
    const set = (next) => onChange(normalize4(next, option));
    const toggleAuto = (auto) => {
      if (auto) {
        onChange([]);
        return;
      }
      set(Array.from({ length: autoCount }, () => ({ w: 0, name: "" })));
    };
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl25,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement(
        ToggleControl2,
        {
          label: "Equal columns",
          checked: isAuto,
          onChange: toggleAuto,
          __nextHasNoMarginBottom: true
        }
      ),
      !isAuto && /* @__PURE__ */ wp.element.createElement(wp.element.Fragment, null, /* @__PURE__ */ wp.element.createElement(
        "div",
        {
          style: {
            display: "flex",
            gap: "2px",
            height: "14px",
            margin: "8px 0"
          },
          "aria-hidden": "true"
        },
        segs.map((s, i) => /* @__PURE__ */ wp.element.createElement(
          "span",
          {
            key: i,
            style: {
              width: `${s.w}%`,
              background: i % 2 ? "#c3c4c7" : "var(--wp-admin-theme-color)",
              borderRadius: "2px"
            }
          }
        ))
      ), segs.map((s, i) => /* @__PURE__ */ wp.element.createElement(
        "div",
        {
          key: i,
          style: { display: "flex", gap: "6px", alignItems: "flex-end" }
        },
        /* @__PURE__ */ wp.element.createElement(
          TextControl6,
          {
            type: "number",
            label: `Pane ${i + 1} (%)`,
            value: String(s.w),
            onChange: (next) => set(
              segs.map(
                (seg, j) => j === i ? { ...seg, w: parseFloat(next) || 0 } : seg
              )
            ),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true
          }
        ),
        option.allow_names !== false && /* @__PURE__ */ wp.element.createElement(
          TextControl6,
          {
            label: "Name",
            value: s.name || "",
            onChange: (next) => onChange(
              segs.map(
                (seg, j) => j === i ? { ...seg, name: next } : seg
              )
            ),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true
          }
        ),
        !option.locked && segs.length > min && /* @__PURE__ */ wp.element.createElement(
          Button14,
          {
            icon: "trash",
            size: "small",
            isDestructive: true,
            label: "Remove pane",
            onClick: () => set(segs.filter((_s, j) => j !== i))
          }
        )
      )), !option.locked && segs.length < max && /* @__PURE__ */ wp.element.createElement(
        Button14,
        {
          variant: "secondary",
          onClick: () => set([...segs, { w: 0, name: "" }]),
          style: { marginTop: "8px" },
          __next40pxDefaultSize: true
        },
        "Add pane"
      ))
    );
  }

  // ../framework/static/js/controls/src/controls/popover.jsx
  var { BaseControl: BaseControl26, Notice: Notice7 } = wp.components;
  var { useMemo: useMemo4 } = wp.element;
  function flatten3(options) {
    const out = [];
    Object.keys(options || {}).forEach((id) => {
      const option = options[id];
      if (!option || typeof option !== "object") {
        return;
      }
      if (option.options) {
        out.push(...flatten3(option.options));
        return;
      }
      if (option.type) {
        out.push([id, option]);
      }
    });
    return out;
  }
  function collect(option) {
    let defs = { ...option["inner-options"] || {} };
    if (Array.isArray(option.tabs)) {
      option.tabs.forEach((tab) => {
        if (tab && tab.options) {
          defs = { ...defs, ...tab.options };
        }
      });
    }
    return flatten3(defs);
  }
  function Field3({ option, value, onChange }) {
    const Control = get(option.type);
    if (!Control) {
      return /* @__PURE__ */ wp.element.createElement(Notice7, { status: "warning", isDismissible: false }, `No React control for "${option.type}" yet \u2014 edit this in the page builder.`);
    }
    return /* @__PURE__ */ wp.element.createElement(Control, { option, value, onChange });
  }
  function Popover({ option = {}, value, onChange }) {
    const inner = useMemo4(
      () => collect(option),
      [option["inner-options"], option.tabs]
    );
    if (!inner.length) {
      return null;
    }
    if (inner.length === 1) {
      const [id, sub] = inner[0];
      return /* @__PURE__ */ wp.element.createElement(
        Field3,
        {
          option: {
            ...sub,
            label: sub.label || option.label || id,
            desc: sub.desc || option.desc
          },
          value: value !== void 0 ? value : option.value,
          onChange
        }
      );
    }
    const current = value && typeof value === "object" ? value : {};
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl26,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement("div", { style: { paddingLeft: "12px", borderLeft: "2px solid #ddd" } }, inner.map(([id, sub]) => /* @__PURE__ */ wp.element.createElement(
        Field3,
        {
          key: id,
          option: sub,
          value: current[id],
          onChange: (next) => onChange({ ...current, [id]: next })
        }
      )))
    );
  }

  // ../framework/static/js/controls/src/controls/box-shadow.jsx
  var { BaseControl: BaseControl27, TextControl: TextControl7, ToggleControl: ToggleControl3, ColorPicker: ColorPicker4, Dropdown, Button: Button15 } = wp.components;
  var DEFAULTS2 = { x: 0, y: 0, blur: 0, spread: 0, color: "", inset: false };
  function BoxShadow({ option = {}, value, onChange }) {
    const current = { ...DEFAULTS2, ...option.value || {}, ...value || {} };
    const setNumber = (key, next, min) => {
      const n = Math.round(parseFloat(next) || 0);
      onChange({ ...current, [key]: min !== void 0 ? Math.max(min, n) : n });
    };
    const preview = `${current.inset ? "inset " : ""}${current.x}px ${current.y}px ${current.blur}px ${current.spread}px ${current.color || "rgba(0,0,0,.2)"}`;
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl27,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement(
        "div",
        {
          style: {
            height: "44px",
            margin: "0 6px 10px",
            borderRadius: "4px",
            background: "#fff",
            border: "1px solid #f0f0f0",
            boxShadow: preview
          },
          "aria-hidden": "true"
        }
      ),
      /* @__PURE__ */ wp.element.createElement("div", { style: { display: "grid", gridTemplateColumns: "1fr 1fr", gap: "8px" } }, /* @__PURE__ */ wp.element.createElement(
        TextControl7,
        {
          type: "number",
          label: "X",
          value: String(current.x),
          onChange: (next) => setNumber("x", next),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ), /* @__PURE__ */ wp.element.createElement(
        TextControl7,
        {
          type: "number",
          label: "Y",
          value: String(current.y),
          onChange: (next) => setNumber("y", next),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ), /* @__PURE__ */ wp.element.createElement(
        TextControl7,
        {
          type: "number",
          label: "Blur",
          min: 0,
          value: String(current.blur),
          onChange: (next) => setNumber("blur", next, 0),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ), /* @__PURE__ */ wp.element.createElement(
        TextControl7,
        {
          type: "number",
          label: "Spread",
          min: 0,
          value: String(current.spread),
          onChange: (next) => setNumber("spread", next, 0),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      )),
      /* @__PURE__ */ wp.element.createElement("div", { style: { marginTop: "8px" } }, /* @__PURE__ */ wp.element.createElement(
        Dropdown,
        {
          renderToggle: ({ isOpen, onToggle }) => /* @__PURE__ */ wp.element.createElement(
            Button15,
            {
              variant: "secondary",
              onClick: onToggle,
              "aria-expanded": isOpen,
              __next40pxDefaultSize: true
            },
            /* @__PURE__ */ wp.element.createElement(
              "span",
              {
                style: {
                  display: "inline-block",
                  width: "14px",
                  height: "14px",
                  marginRight: "8px",
                  borderRadius: "2px",
                  border: "1px solid #ddd",
                  background: current.color || "transparent"
                },
                "aria-hidden": "true"
              }
            ),
            "Shadow colour"
          ),
          renderContent: () => /* @__PURE__ */ wp.element.createElement(
            ColorPicker4,
            {
              color: current.color || void 0,
              enableAlpha: true,
              onChange: (next) => onChange({ ...current, color: next })
            }
          )
        }
      )),
      /* @__PURE__ */ wp.element.createElement(
        ToggleControl3,
        {
          label: "Inset",
          checked: Boolean(current.inset),
          onChange: (next) => onChange({ ...current, inset: next }),
          __nextHasNoMarginBottom: true
        }
      )
    );
  }

  // ../framework/static/js/controls/src/controls/responsive.jsx
  var { BaseControl: BaseControl28, Button: Button16, Notice: Notice8 } = wp.components;
  var { useState: useState7 } = wp.element;
  var DEVICES2 = [
    { key: "base", label: "Mobile", icon: "smartphone" },
    { key: "md", label: "Tablet", icon: "tablet" },
    { key: "lg", label: "Desktop", icon: "desktop" }
  ];
  function Responsive({ option = {}, value, onChange }) {
    const [device, setDevice] = useState7("base");
    const inner = option.inner || { type: "short-select", choices: {} };
    const Control = get(inner.type);
    const current = {
      base: "",
      md: "",
      lg: "",
      ...option.value && typeof option.value === "object" ? option.value : {},
      ...value && typeof value === "object" ? value : {}
    };
    const choices = inner.choices;
    const canBeBlank = !choices || typeof choices !== "object" || Object.prototype.hasOwnProperty.call(choices, "");
    if (!Control) {
      return /* @__PURE__ */ wp.element.createElement(Notice8, { status: "warning", isDismissible: false }, `No React control for "${inner.type}" yet \u2014 edit this in the page builder.`);
    }
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl28,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement("div", { style: { display: "flex", gap: "2px", marginBottom: "8px" } }, DEVICES2.map((d) => {
        const isSet = current[d.key] !== "" && current[d.key] !== void 0;
        return /* @__PURE__ */ wp.element.createElement(
          Button16,
          {
            key: d.key,
            icon: d.icon,
            label: isSet ? `${d.label} (set)` : d.label,
            showTooltip: true,
            isPressed: device === d.key,
            onClick: () => setDevice(d.key),
            style: {
              /*
               * A dot marks a device that carries its own value, so the
               * inherited ones are visible at a glance. Without it there is
               * no way to tell a device you have set from one you have not
               * without clicking each in turn.
               */
              position: "relative",
              boxShadow: isSet ? "inset 0 -2px 0 var(--wp-admin-theme-color)" : void 0
            }
          }
        );
      })),
      /* @__PURE__ */ wp.element.createElement(
        Control,
        {
          option: {
            ...inner,
            label: DEVICES2.find((d) => d.key === device).label,
            desc: device === "base" ? "Applies to every width unless a larger one overrides it." : canBeBlank ? "Leave blank to inherit the smaller width." : void 0
          },
          value: current[device],
          onChange: (next) => onChange({ ...current, [device]: next })
        }
      )
    );
  }

  // ../framework/static/js/controls/src/controls/gradient-v2.jsx
  var { BaseControl: BaseControl29, Button: Button17, ColorPicker: ColorPicker5, Dropdown: Dropdown2, RangeControl: RangeControl2, SelectControl: SelectControl8 } = wp.components;
  var STARTER = [
    { color: "#3858e9", position: 0 },
    { color: "#7f54b3", position: 100 }
  ];
  function toCss(value) {
    const stops = Array.isArray(value.stops) ? value.stops : [];
    if (stops.length < 2) {
      return "";
    }
    const list = [...stops].sort((a, b) => a.position - b.position).map((s) => `${s.color} ${s.position}%`).join(", ");
    return value.type === "radial" ? `radial-gradient(circle, ${list})` : `linear-gradient(${value.angle}deg, ${list})`;
  }
  function GradientV2({ option = {}, value, onChange }) {
    const current = {
      type: "linear",
      angle: 90,
      stops: [],
      ...option.value && typeof option.value === "object" ? option.value : {},
      ...value && typeof value === "object" ? value : {}
    };
    const stops = Array.isArray(current.stops) ? current.stops : [];
    const on = stops.length >= 2;
    const set = (next) => onChange({ ...current, ...next });
    const setStop = (index, patch) => set({ stops: stops.map((s, i) => i === index ? { ...s, ...patch } : s) });
    const removeStop = (index) => {
      const next = stops.filter((_s, i) => i !== index);
      set({ stops: next.length < 2 ? [] : next });
    };
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl29,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement(
        "div",
        {
          style: {
            height: "32px",
            borderRadius: "4px",
            border: "1px solid #ddd",
            marginBottom: "8px",
            background: on ? toCss(current) : "repeating-linear-gradient(45deg,#f0f0f0 0 6px,#fff 6px 12px)"
          },
          "aria-hidden": "true"
        }
      ),
      !on ? /* @__PURE__ */ wp.element.createElement(
        Button17,
        {
          variant: "secondary",
          onClick: () => set({ stops: STARTER }),
          __next40pxDefaultSize: true
        },
        "Add a gradient"
      ) : /* @__PURE__ */ wp.element.createElement(wp.element.Fragment, null, /* @__PURE__ */ wp.element.createElement(
        SelectControl8,
        {
          label: "Type",
          value: current.type,
          options: [
            { label: "Linear", value: "linear" },
            { label: "Radial", value: "radial" }
          ],
          onChange: (next) => set({ type: next }),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ), current.type === "linear" && /* @__PURE__ */ wp.element.createElement(
        RangeControl2,
        {
          label: "Angle",
          value: current.angle,
          min: 0,
          max: 360,
          onChange: (next) => set({ angle: Math.round(next != null ? next : 90) }),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ), stops.map((stop, index) => /* @__PURE__ */ wp.element.createElement(
        "div",
        {
          key: index,
          style: {
            display: "flex",
            alignItems: "center",
            gap: "6px",
            marginTop: "8px"
          }
        },
        /* @__PURE__ */ wp.element.createElement(
          Dropdown2,
          {
            renderToggle: ({ onToggle }) => /* @__PURE__ */ wp.element.createElement(
              Button17,
              {
                onClick: onToggle,
                label: `Stop ${index + 1} colour`,
                showTooltip: true,
                style: {
                  width: "28px",
                  height: "28px",
                  padding: 0,
                  borderRadius: "3px",
                  border: "1px solid #ddd",
                  background: stop.color
                }
              }
            ),
            renderContent: () => /* @__PURE__ */ wp.element.createElement(
              ColorPicker5,
              {
                color: stop.color,
                enableAlpha: true,
                onChange: (next) => setStop(index, { color: next })
              }
            )
          }
        ),
        /* @__PURE__ */ wp.element.createElement("div", { style: { flex: "1 1 auto" } }, /* @__PURE__ */ wp.element.createElement(
          RangeControl2,
          {
            value: stop.position,
            min: 0,
            max: 100,
            onChange: (next) => setStop(index, { position: Math.max(0, Math.min(100, next != null ? next : 0)) }),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true
          }
        )),
        /* @__PURE__ */ wp.element.createElement(
          Button17,
          {
            icon: "trash",
            size: "small",
            isDestructive: true,
            label: "Remove stop",
            onClick: () => removeStop(index)
          }
        )
      )), /* @__PURE__ */ wp.element.createElement(
        Button17,
        {
          variant: "tertiary",
          onClick: () => set({
            stops: [...stops, { color: "#ffffff", position: 100 }]
          }),
          style: { marginTop: "8px" }
        },
        "Add stop"
      ))
    );
  }

  // ../framework/static/js/controls/src/controls/rgba-color-picker.jsx
  var { BaseControl: BaseControl30, Button: Button18, ColorPicker: ColorPicker6, Dropdown: Dropdown3 } = wp.components;
  function RgbaColorPicker({ option = {}, value, onChange }) {
    const current = typeof value === "string" ? value : option.value || "";
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl30,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement("div", { style: { display: "flex", gap: "6px", alignItems: "center" } }, /* @__PURE__ */ wp.element.createElement(
        Dropdown3,
        {
          renderToggle: ({ isOpen, onToggle }) => /* @__PURE__ */ wp.element.createElement(
            Button18,
            {
              variant: "secondary",
              onClick: onToggle,
              "aria-expanded": isOpen,
              __next40pxDefaultSize: true
            },
            /* @__PURE__ */ wp.element.createElement(
              "span",
              {
                style: {
                  display: "inline-block",
                  width: "16px",
                  height: "16px",
                  marginRight: "8px",
                  borderRadius: "3px",
                  border: "1px solid #ddd",
                  // A chequerboard behind the swatch, so a transparent or
                  // semi-transparent colour reads as transparent rather than
                  // as white.
                  backgroundImage: "repeating-linear-gradient(45deg,#e0e0e0 0 4px,#fff 4px 8px)"
                },
                "aria-hidden": "true"
              },
              /* @__PURE__ */ wp.element.createElement(
                "span",
                {
                  style: {
                    display: "block",
                    width: "100%",
                    height: "100%",
                    background: current || "transparent"
                  }
                }
              )
            ),
            current || "None"
          ),
          renderContent: () => /* @__PURE__ */ wp.element.createElement(ColorPicker6, { color: current || void 0, enableAlpha: true, onChange })
        }
      ), current !== "" && /* @__PURE__ */ wp.element.createElement(
        Button18,
        {
          icon: "no-alt",
          size: "small",
          label: "Clear",
          onClick: () => onChange("")
        }
      ))
    );
  }

  // ../framework/static/js/controls/src/controls/background-pro.jsx
  var { BaseControl: BaseControl31, Button: Button19, PanelBody, SelectControl: SelectControl9, TextControl: TextControl8, ToggleControl: ToggleControl4 } = wp.components;
  var DEFAULTS3 = {
    color: { value: { predefined: "", custom: "" } },
    gradient: { data: { type: "linear", angle: 90, stops: [] } },
    image: {
      src: {},
      position: "center center",
      size: { selected: "cover", custom: "" },
      repeat: "no-repeat",
      attachment: "scroll"
    },
    video: {
      enabled: "no",
      external_url: "",
      source_mp4: {},
      source_webm: {},
      poster: {},
      fallback: {},
      loop: "yes",
      autoplay: "yes",
      mute: "yes",
      playsinline: "yes",
      allow_interaction: "no"
    },
    overlay: { color: "", gradient: { type: "linear", angle: 90, stops: [] } },
    advanced: []
  };
  var POSITIONS = [
    "top left",
    "top center",
    "top right",
    "center left",
    "center center",
    "center right",
    "bottom left",
    "bottom center",
    "bottom right"
  ];
  var REPEATS = [
    ["no-repeat", "No Repeat"],
    ["repeat", "Repeat (Tile)"],
    ["repeat-x", "Repeat Horizontally"],
    ["repeat-y", "Repeat Vertically"],
    ["space", "Space (No Crop)"],
    ["round", "Round (Stretch to Whole Tiles)"]
  ];
  var ATTACHMENTS = [
    ["scroll", "Scroll"],
    ["fixed", "Fixed (Parallax)"],
    ["local", "Local"]
  ];
  var SIZES = [
    ["auto", "Auto"],
    ["cover", "Cover"],
    ["contain", "Contain"],
    ["custom", "Custom"]
  ];
  function child(type, schema, value, onChange) {
    const Control = get(type);
    if (!Control) {
      return null;
    }
    return /* @__PURE__ */ wp.element.createElement(Control, { option: { type, ...schema }, value, onChange });
  }
  function hasVideo(video) {
    return Boolean(
      video.external_url && String(video.external_url).trim() !== "" || video.source_mp4 && video.source_mp4.url || video.source_webm && video.source_webm.url
    );
  }
  function BackgroundPro({ option = {}, value, onChange }) {
    const v = value && typeof value === "object" ? value : {};
    const current = {
      color: { ...DEFAULTS3.color, ...v.color || {} },
      gradient: { ...DEFAULTS3.gradient, ...v.gradient || {} },
      image: { ...DEFAULTS3.image, ...v.image || {} },
      video: { ...DEFAULTS3.video, ...v.video || {} },
      overlay: { ...DEFAULTS3.overlay, ...v.overlay || {} },
      advanced: v.advanced || DEFAULTS3.advanced
    };
    current.image.size = { ...DEFAULTS3.image.size, ...current.image.size || {} };
    const disabled = Array.isArray(option.disable) ? option.disable : option.disable ? [option.disable] : [];
    const shows = (layer) => !disabled.includes(layer);
    const setLayer = (layer, patch) => {
      const next = { ...current, [layer]: { ...current[layer], ...patch } };
      if (layer === "video") {
        next.video.enabled = hasVideo(next.video) ? "yes" : "no";
      }
      onChange(next);
    };
    const sw = (layer, key, label) => /* @__PURE__ */ wp.element.createElement(
      ToggleControl4,
      {
        label,
        checked: current[layer][key] === "yes",
        onChange: (on) => setLayer(layer, { [key]: on ? "yes" : "no" }),
        __nextHasNoMarginBottom: true
      }
    );
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl31,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      shows("color") && /* @__PURE__ */ wp.element.createElement(PanelBody, { title: "Colour", initialOpen: false }, child(
        "predefined-colors-color-picker-compact",
        { label: "" },
        current.color.value,
        (next) => onChange({ ...current, color: { value: next } })
      )),
      shows("gradient") && /* @__PURE__ */ wp.element.createElement(PanelBody, { title: "Gradient", initialOpen: false }, child(
        "gradient-v2",
        { label: "" },
        current.gradient.data,
        (next) => onChange({ ...current, gradient: { data: next } })
      )),
      shows("image") && /* @__PURE__ */ wp.element.createElement(PanelBody, { title: "Image", initialOpen: false }, child(
        "upload",
        { label: "Image", images_only: true },
        current.image.src,
        (next) => setLayer("image", { src: next })
      ), /* @__PURE__ */ wp.element.createElement(
        SelectControl9,
        {
          label: "Position",
          value: current.image.position,
          options: POSITIONS.map((p) => ({
            value: p,
            label: p.replace(/\b\w/g, (c) => c.toUpperCase())
          })),
          onChange: (next) => setLayer("image", { position: next }),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ), /* @__PURE__ */ wp.element.createElement(
        SelectControl9,
        {
          label: "Size",
          value: current.image.size.selected,
          options: SIZES.map(([value_, label]) => ({ value: value_, label })),
          onChange: (next) => setLayer("image", { size: { ...current.image.size, selected: next } }),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ), current.image.size.selected === "custom" && /* @__PURE__ */ wp.element.createElement(
        TextControl8,
        {
          label: "Custom size",
          help: 'e.g. "400px" or "100% 50%"',
          value: current.image.size.custom,
          onChange: (next) => setLayer("image", { size: { ...current.image.size, custom: next } }),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ), /* @__PURE__ */ wp.element.createElement(
        SelectControl9,
        {
          label: "Repeat",
          value: current.image.repeat,
          options: REPEATS.map(([value_, label]) => ({ value: value_, label })),
          onChange: (next) => setLayer("image", { repeat: next }),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ), /* @__PURE__ */ wp.element.createElement(
        SelectControl9,
        {
          label: "Attachment",
          value: current.image.attachment,
          options: ATTACHMENTS.map(([value_, label]) => ({ value: value_, label })),
          onChange: (next) => setLayer("image", { attachment: next }),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      )),
      shows("video") && /* @__PURE__ */ wp.element.createElement(PanelBody, { title: "Video", initialOpen: false }, /* @__PURE__ */ wp.element.createElement(
        TextControl8,
        {
          label: "External URL",
          value: current.video.external_url,
          onChange: (next) => setLayer("video", { external_url: next }),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ), child(
        "upload",
        { label: "MP4 file" },
        current.video.source_mp4,
        (next) => setLayer("video", { source_mp4: next })
      ), child(
        "upload",
        { label: "WebM file" },
        current.video.source_webm,
        (next) => setLayer("video", { source_webm: next })
      ), child(
        "upload",
        { label: "Poster", images_only: true },
        current.video.poster,
        (next) => setLayer("video", { poster: next })
      ), child(
        "upload",
        { label: "Fallback image", images_only: true },
        current.video.fallback,
        (next) => setLayer("video", { fallback: next })
      ), sw("video", "loop", "Loop"), sw("video", "autoplay", "Autoplay"), sw("video", "mute", "Mute"), sw("video", "playsinline", "Play inline"), sw("video", "allow_interaction", "Allow interaction")),
      shows("overlay") && /* @__PURE__ */ wp.element.createElement(PanelBody, { title: "Overlay", initialOpen: false }, child(
        "rgba-color-picker",
        { label: "Tint" },
        current.overlay.color,
        (next) => setLayer("overlay", { color: next })
      ), child(
        "gradient-v2",
        { label: "Overlay gradient" },
        current.overlay.gradient,
        (next) => setLayer("overlay", { gradient: next })
      ))
    );
  }

  // ../framework/static/js/controls/src/controls/table.jsx
  var { BaseControl: BaseControl32, Button: Button20, TextControl: TextControl9, TextareaControl: TextareaControl4, SelectControl: SelectControl10 } = wp.components;
  var ALIGNS = [
    { value: "", label: "Default" },
    { value: "left", label: "Left" },
    { value: "center", label: "Center" },
    { value: "right", label: "Right" }
  ];
  var blankCell = () => ({ textarea: "", colspan: 1, rowspan: 1, merged: false });
  function Table({ option = {}, value, onChange }) {
    const v = value && typeof value === "object" ? value : {};
    const header = {
      table_purpose: "tabular",
      header_rows: 0,
      footer_rows: 0,
      ...v.header_options || {}
    };
    const cols = Array.isArray(v.cols) && v.cols.length ? v.cols : [{ name: "default-col", align: "", width: "" }];
    const content = Array.isArray(v.content) && v.content.length ? v.content : [cols.map(blankCell)];
    const commit = (nextCols, nextContent, nextHeader = header) => {
      const headerRows = Math.min(Math.max(0, nextHeader.header_rows), nextContent.length);
      const footerRows = Math.min(
        Math.max(0, nextHeader.footer_rows),
        Math.max(0, nextContent.length - headerRows)
      );
      onChange({
        header_options: {
          table_purpose: nextHeader.table_purpose === "pricing" ? "pricing" : "tabular",
          header_rows: headerRows,
          footer_rows: footerRows
        },
        cols: nextCols,
        // Derived from position, exactly as the PHP derives it.
        rows: nextContent.map((_row, i) => ({
          name: i < headerRows ? "heading-row" : "default-row"
        })),
        content: nextContent.map(
          (row) => nextCols.map((_c, ci) => row[ci] || blankCell())
        )
      });
    };
    const setCell = (ri, ci, text) => commit(
      cols,
      content.map(
        (row, r) => r !== ri ? row : row.map(
          (cell, c) => (
            // Spread, never rebuild: colspan / rowspan / merged survive.
            c === ci ? { ...cell || blankCell(), textarea: text } : cell
          )
        )
      )
    );
    const addRow = () => commit(cols, [...content, cols.map(blankCell)]);
    const removeRow = (ri) => commit(cols, content.filter((_r, i) => i !== ri));
    const addCol = () => commit(
      [...cols, { name: "default-col", align: "", width: "" }],
      content.map((row) => [...row, blankCell()])
    );
    const removeCol = (ci) => commit(
      cols.filter((_c, i) => i !== ci),
      content.map((row) => row.filter((_c, i) => i !== ci))
    );
    const setCol = (ci, patch) => commit(cols.map((c, i) => i === ci ? { ...c, ...patch } : c), content);
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl32,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement("div", { style: { display: "flex", gap: "8px" } }, /* @__PURE__ */ wp.element.createElement(
        TextControl9,
        {
          type: "number",
          label: "Header rows",
          min: 0,
          value: String(header.header_rows),
          onChange: (next) => commit(cols, content, {
            ...header,
            header_rows: parseInt(next, 10) || 0
          }),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      ), /* @__PURE__ */ wp.element.createElement(
        TextControl9,
        {
          type: "number",
          label: "Footer rows",
          min: 0,
          value: String(header.footer_rows),
          onChange: (next) => commit(cols, content, {
            ...header,
            footer_rows: parseInt(next, 10) || 0
          }),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      )),
      cols.map((col, ci) => /* @__PURE__ */ wp.element.createElement(
        "div",
        {
          key: ci,
          style: {
            display: "flex",
            gap: "6px",
            alignItems: "flex-end",
            marginTop: "8px"
          }
        },
        /* @__PURE__ */ wp.element.createElement(
          SelectControl10,
          {
            label: `Column ${ci + 1}`,
            value: col.align || "",
            options: ALIGNS,
            onChange: (next) => setCol(ci, { align: next }),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true
          }
        ),
        /* @__PURE__ */ wp.element.createElement(
          TextControl9,
          {
            label: "Width",
            value: col.width || "",
            onChange: (next) => setCol(ci, { width: next }),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true
          }
        ),
        cols.length > 1 && /* @__PURE__ */ wp.element.createElement(
          Button20,
          {
            icon: "trash",
            size: "small",
            isDestructive: true,
            label: `Remove column ${ci + 1}`,
            onClick: () => removeCol(ci)
          }
        )
      )),
      /* @__PURE__ */ wp.element.createElement("div", { style: { marginTop: "12px" } }, content.map((row, ri) => /* @__PURE__ */ wp.element.createElement("div", { key: ri, style: { marginBottom: "10px" } }, /* @__PURE__ */ wp.element.createElement(
        "div",
        {
          style: {
            display: "flex",
            justifyContent: "space-between",
            alignItems: "center",
            fontSize: "11px",
            opacity: 0.75
          }
        },
        /* @__PURE__ */ wp.element.createElement("span", null, ri < header.header_rows ? `Header row ${ri + 1}` : `Row ${ri + 1}`),
        content.length > 1 && /* @__PURE__ */ wp.element.createElement(
          Button20,
          {
            icon: "trash",
            size: "small",
            isDestructive: true,
            label: `Remove row ${ri + 1}`,
            onClick: () => removeRow(ri)
          }
        )
      ), cols.map((_col, ci) => {
        const cell = row[ci] || blankCell();
        if (cell.merged) {
          return /* @__PURE__ */ wp.element.createElement(
            "p",
            {
              key: ci,
              style: { margin: "2px 0", fontSize: "11px", fontStyle: "italic", opacity: 0.6 }
            },
            `Column ${ci + 1}: merged`
          );
        }
        return /* @__PURE__ */ wp.element.createElement(
          TextareaControl4,
          {
            key: ci,
            label: `Column ${ci + 1}`,
            value: cell.textarea || "",
            rows: 2,
            onChange: (next) => setCell(ri, ci, next),
            __nextHasNoMarginBottom: true
          }
        );
      })))),
      /* @__PURE__ */ wp.element.createElement("div", { style: { display: "flex", gap: "6px" } }, /* @__PURE__ */ wp.element.createElement(Button20, { variant: "secondary", onClick: addRow, __next40pxDefaultSize: true }, "Add row"), /* @__PURE__ */ wp.element.createElement(Button20, { variant: "secondary", onClick: addCol, __next40pxDefaultSize: true }, "Add column"))
    );
  }

  // ../framework/static/js/controls/src/controls/table-style-picker.jsx
  var { BaseControl: BaseControl33, Button: Button21 } = wp.components;
  function TableStylePicker({ option = {}, value, onChange }) {
    const choices = option.choices && typeof option.choices === "object" ? option.choices : {};
    const allowNone = option.allow_none === void 0 || option.allow_none;
    const current = typeof value === "string" && choices[value] !== void 0 ? value : "";
    const sample = (key) => /* @__PURE__ */ wp.element.createElement(
      "table",
      {
        className: key || void 0,
        style: { width: "100%", borderCollapse: "collapse", fontSize: "9px", pointerEvents: "none" },
        "aria-hidden": "true"
      },
      /* @__PURE__ */ wp.element.createElement("thead", null, /* @__PURE__ */ wp.element.createElement("tr", null, /* @__PURE__ */ wp.element.createElement("th", null, "A"), /* @__PURE__ */ wp.element.createElement("th", null, "B"))),
      /* @__PURE__ */ wp.element.createElement("tbody", null, /* @__PURE__ */ wp.element.createElement("tr", null, /* @__PURE__ */ wp.element.createElement("td", null, "1"), /* @__PURE__ */ wp.element.createElement("td", null, "2")), /* @__PURE__ */ wp.element.createElement("tr", null, /* @__PURE__ */ wp.element.createElement("td", null, "3"), /* @__PURE__ */ wp.element.createElement("td", null, "4")))
    );
    const row = (key, label, selected) => /* @__PURE__ */ wp.element.createElement(
      Button21,
      {
        key: key || "__none",
        onClick: () => onChange(key),
        "aria-pressed": selected,
        style: {
          display: "block",
          width: "100%",
          height: "auto",
          padding: "6px",
          marginBottom: "6px",
          borderRadius: "4px",
          textAlign: "left",
          boxShadow: selected ? "0 0 0 2px var(--wp-admin-theme-color)" : "inset 0 0 0 1px #ddd"
        }
      },
      key ? sample(key) : /* @__PURE__ */ wp.element.createElement("span", { style: { fontStyle: "italic", opacity: 0.7 } }, option.placeholder || "\u2014 Select \u2014"),
      /* @__PURE__ */ wp.element.createElement("span", { style: { display: "block", marginTop: "4px", fontSize: "11px" } }, label)
    );
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl33,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      /* @__PURE__ */ wp.element.createElement("div", null, allowNone && row("", choices[""] || "None", current === ""), Object.keys(choices).filter((key) => key !== "").map((key) => row(key, choices[key] || key, current === key)))
    );
  }

  // ../framework/static/js/controls/src/controls/form-builder.jsx
  var { BaseControl: BaseControl34, Button: Button22, Card: Card2, CardBody: CardBody2, Notice: Notice9, SelectControl: SelectControl11 } = wp.components;
  var { useState: useState8 } = wp.element;
  function mintShortcode(type, existing) {
    const used = new Set(existing.map((i) => i && i.shortcode).filter(Boolean));
    const base = String(type).replace(/-/g, "_").toLowerCase();
    for (let attempt = 0; attempt < 50; attempt++) {
      const suffix = Math.random().toString(16).slice(2, 9).padEnd(7, "0");
      const candidate = `${base}_${suffix}`;
      if (!used.has(candidate)) {
        return candidate;
      }
    }
    return `${base}_${existing.length}`;
  }
  function decode(value) {
    if (!value || typeof value !== "object") {
      return [];
    }
    if (Array.isArray(value.json)) {
      return value.json;
    }
    if (typeof value.json !== "string") {
      return [];
    }
    try {
      const parsed = JSON.parse(value.json);
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return null;
    }
  }
  function FormBuilder({ option = {}, value, onChange }) {
    const [openIndex, setOpenIndex] = useState8(null);
    const items = decode(value);
    const itemTypes = option.item_types && typeof option.item_types === "object" ? option.item_types : {};
    if (items === null) {
      return /* @__PURE__ */ wp.element.createElement(Notice9, { status: "warning", isDismissible: false }, "This form could not be read here \u2014 edit it in the page builder. Nothing has been changed.");
    }
    const commit = (next) => onChange({ ...value || {}, json: JSON.stringify(next) });
    const addField = (type) => {
      if (!type) {
        return;
      }
      const declared = itemTypes[type] ? itemTypes[type].options || {} : {};
      const options = {};
      Object.keys(declared).forEach((id) => {
        if (declared[id].value !== void 0) {
          options[id] = declared[id].value;
        }
      });
      commit([
        ...items,
        {
          type,
          shortcode: mintShortcode(type, items),
          // The page builder gives every item a width from its grid; '' is the
          // element's own "auto" and is what the seeded default form uses.
          width: "",
          options
        }
      ]);
      setOpenIndex(items.length);
    };
    const setAttr = (index, id, next) => commit(
      items.map(
        (item, i) => i === index ? { ...item, options: { ...item.options || {}, [id]: next } } : item
      )
    );
    const move = (index, delta) => {
      const target = index + delta;
      if (target < 0 || target >= items.length) {
        return;
      }
      const next = items.slice();
      [next[index], next[target]] = [next[target], next[index]];
      commit(next);
      setOpenIndex(openIndex === index ? target : openIndex);
    };
    const remove = (index) => {
      commit(items.filter((_item, i) => i !== index));
      setOpenIndex(null);
    };
    const duplicate = (index) => {
      const copy = { ...items[index], shortcode: mintShortcode(items[index].type, items) };
      const next = items.slice();
      next.splice(index + 1, 0, copy);
      commit(next);
    };
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl34,
      {
        label: option.label || "Form fields",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      items.map((item, index) => {
        const known = itemTypes[item.type];
        const fields = known ? known.options || {} : {};
        const isOpen = openIndex === index;
        const attrs = item && item.options || {};
        const title = attrs.label && String(attrs.label).trim() || known && known.title || item.type;
        return /* @__PURE__ */ wp.element.createElement(Card2, { key: item.shortcode || index, size: "small", style: { marginBottom: "8px" } }, /* @__PURE__ */ wp.element.createElement(CardBody2, { style: { padding: "8px" } }, /* @__PURE__ */ wp.element.createElement("div", { style: { display: "flex", alignItems: "center", gap: "2px" } }, /* @__PURE__ */ wp.element.createElement(
          Button22,
          {
            variant: "tertiary",
            onClick: () => setOpenIndex(isOpen ? null : index),
            "aria-expanded": isOpen,
            style: {
              flex: "1 1 auto",
              justifyContent: "flex-start",
              minWidth: 0,
              overflow: "hidden",
              textOverflow: "ellipsis"
            }
          },
          title,
          attrs.required ? " *" : ""
        ), /* @__PURE__ */ wp.element.createElement(
          Button22,
          {
            icon: "arrow-up-alt2",
            size: "small",
            label: "Move up",
            disabled: index === 0,
            onClick: () => move(index, -1)
          }
        ), /* @__PURE__ */ wp.element.createElement(
          Button22,
          {
            icon: "arrow-down-alt2",
            size: "small",
            label: "Move down",
            disabled: index === items.length - 1,
            onClick: () => move(index, 1)
          }
        ), /* @__PURE__ */ wp.element.createElement(
          Button22,
          {
            icon: "admin-page",
            size: "small",
            label: "Duplicate",
            onClick: () => duplicate(index)
          }
        ), /* @__PURE__ */ wp.element.createElement(
          Button22,
          {
            icon: "trash",
            size: "small",
            isDestructive: true,
            label: "Remove",
            onClick: () => remove(index)
          }
        )), isOpen && /* @__PURE__ */ wp.element.createElement("div", { style: { marginTop: "12px" } }, !known && /* @__PURE__ */ wp.element.createElement(Notice9, { status: "warning", isDismissible: false }, `Unknown field type "${item.type}" \u2014 edit it in the page builder.`), Object.keys(fields).map((id) => {
          const sub = fields[id];
          const Control = get(sub.type);
          if (!Control) {
            return /* @__PURE__ */ wp.element.createElement(Notice9, { key: id, status: "warning", isDismissible: false }, `No React control for "${sub.type}" yet.`);
          }
          return /* @__PURE__ */ wp.element.createElement(
            Control,
            {
              key: id,
              option: sub,
              value: attrs[id],
              onChange: (next) => setAttr(index, id, next)
            }
          );
        }), /* @__PURE__ */ wp.element.createElement("p", { style: { margin: "8px 0 0", fontSize: "11px", opacity: 0.7 } }, `Field name: ${item.shortcode}`))));
      }),
      /* @__PURE__ */ wp.element.createElement(
        SelectControl11,
        {
          label: "Add a field",
          value: "",
          options: [
            { label: "\u2014 Choose a field type \u2014", value: "" },
            ...Object.keys(itemTypes).map((id) => ({
              value: id,
              label: itemTypes[id].title || id
            }))
          ],
          onChange: addField,
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true
        }
      )
    );
  }

  // ../framework/static/js/controls/src/controls/mailer.jsx
  var { BaseControl: BaseControl35, Notice: Notice10 } = wp.components;
  function Mailer({ option = {}, value }) {
    const configured = value && typeof value === "object" && Object.keys(value).length > 0;
    return /* @__PURE__ */ wp.element.createElement(BaseControl35, { label: option.label || "Mailer", __nextHasNoMarginBottom: true }, /* @__PURE__ */ wp.element.createElement(Notice10, { status: configured ? "success" : "warning", isDismissible: false }, configured ? "Mail delivery is configured for the whole site. Change it under Unyson+ \u2192 Settings \u2192 Mailer, not per form." : "Mail delivery is not configured yet. Set it up under Unyson+ \u2192 Settings \u2192 Mailer \u2014 a form cannot send without it."));
  }

  // ../framework/static/js/controls/src/controls/null-control.jsx
  function NullControl() {
    return null;
  }

  // ../framework/static/js/controls/src/index.jsx
  var { Notice: Notice11 } = wp.components;
  register("text", Text);
  register("switch", Switch);
  register("select", Select);
  register("short-select", Select);
  register("number", Number_);
  register("short-text", Text);
  register("medium-text", Text);
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
  register("addable-popup", AddablePopup);
  register("addable-popup-full", AddablePopup);
  register("multi-picker", MultiPicker);
  register("image-style-picker", ImageStylePicker);
  register("checkboxes", Checkboxes);
  register("button-style-picker", ButtonStylePicker);
  register("button-hover-animation", ButtonHoverAnimation);
  register("column-split", ColumnSplit);
  register("code-editor", CodeEditor);
  register("datetime-picker", DatetimePicker);
  register("multi-upload", MultiUpload);
  register("addable-box", AddableBox);
  register("date-picker", DatePicker);
  register("multi-inline", MultiInline);
  register("fw-multi-inline", MultiInline);
  register("gmap-key", GmapKey);
  register("split-slider", SplitSlider);
  register("popover", Popover);
  register("box-shadow", BoxShadow);
  register("responsive", Responsive);
  register("gradient-v2", GradientV2);
  register("rgba-color-picker", RgbaColorPicker);
  register("background-pro", BackgroundPro);
  register("table", Table);
  register("table-style-picker", TableStylePicker);
  register("form-builder", FormBuilder);
  register("mailer", Mailer);
  register("gallery-3d-preview", NullControl);
  register("html", NullControl);
  register("html-full", NullControl);
  register("html-fixed", NullControl);
  register("svg-code", CodeEditor);
  function Undefined({ type }) {
    return /* @__PURE__ */ wp.element.createElement(Notice11, { status: "warning", isDismissible: false }, `No React control for option type "${type}" yet \u2014 edit this option in the page builder.`);
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
