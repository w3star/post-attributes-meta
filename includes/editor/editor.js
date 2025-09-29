(function (wp) {
  const { registerPlugin } = wp.plugins;
  const P = wp.editor?.PluginDocumentSettingPanel || wp.editPost.PluginDocumentSettingPanel;
  const { PanelRow, SelectControl, RangeControl, TextControl, __experimentalNumberControl: NumberControl } = wp.components;
  const { createElement: h, Fragment } = wp.element;
  const { useSelect, useDispatch } = wp.data;

  const PANELS = window.OWWW_PANELS || [];

  function Field({ field, meta, editMeta }) {
    const key = field.key, raw = meta[key];

    if (field.widget === 'select') {
      const opts = (field.options || []).map(o => ({ label: o.label, value: String(o.value) }));
      return h(PanelRow, null,
        h(SelectControl, {
          label: field.label || key,
          value: String(raw ?? ''),
          options: opts,
          onChange: v => editMeta({ [key]: String(v) })
        })
      );
    }

    if (field.type === 'int' && field.widget === 'input') {
      const Control = NumberControl || TextControl;
      return h(PanelRow, null,
        h('span', { className: 'owww-fieldlabel' }, field.label || key),
        h(Control, {
          label: undefined,
          value: raw ?? '',
          onChange: v => editMeta({ [key]: parseInt(v || 0, 10) || 0 })
        })
      );
    }

    if (field.type === 'int' && field.widget === 'range') {
      const min = field.min ?? 0, max = field.max ?? 100, step = field.step ?? 1;
      return h(PanelRow, null,
        h(RangeControl, {
          label: field.label || key,
          value: parseInt(raw || 0, 10) || 0,
          min, max, step,
          onChange: v => editMeta({ [key]: parseInt(v || 0, 10) || 0 })
        })
      );
    }

    return h(PanelRow, null,
      h(TextControl, {
        label: field.label || key,
        value: raw ?? '',
        onChange: v => editMeta({ [key]: v })
      })
    );
  }

  function Panels() {
    const meta = useSelect(s => s('core/editor').getEditedPostAttribute('meta') || {}, []);
    const { editPost } = useDispatch('core/editor');
    const editMeta = patch => editPost({ meta: { ...meta, ...patch } });

    return h(Fragment, null, PANELS.map(p =>
      h(P, { name: 'owww_' + p.id, title: p.title, className: 'owww-section', key: p.id },
        p.fields.map(f => h(Field, { field: f, meta, editMeta, key: f.key }))
      )
    ));
  }

  registerPlugin('owww-sidebar-panels', { render: Panels });
})(window.wp);
