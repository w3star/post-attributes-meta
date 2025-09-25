/* blocks/pam-sidebar.js */
(function (wp) {
    // Harte Guards + Debug
    if (!wp) { console.error('[PAM] window.wp fehlt'); return; }
    if (!wp.plugins || !wp.editPost || !wp.data || !wp.components || !wp.element) {
        console.error('[PAM] Gutenberg Packages fehlen', { hasPlugins: !!wp.plugins, hasEditPost: !!wp.editPost });
        return;
    }

    const { registerPlugin } = wp.plugins;
    //const { PluginDocumentSettingPanel } = wp.editPost;
    const { PluginDocumentSettingPanel } = wp.editor || wp.editPost;
    const { useSelect, useDispatch } = wp.data;
    const { PanelRow, RangeControl, SelectControl, TextControl, Notice } = wp.components;
    const { createElement: h, Fragment, useMemo } = wp.element;

    const SECTIONS = Array.isArray(window.PAM_SECTIONS) ? window.PAM_SECTIONS : [];
    console.log('[PAM] pam-sidebar.js boot', { sections: SECTIONS });

    // Hilfs-Funktionen
    const toInt = (v) => {
        const n = parseInt(v, 10);
        return isNaN(n) ? 0 : n;
    };

    function FieldControl({ field, meta, editMeta }) {
        const key = field.key;
        const val = meta[key];

        switch (field.type) {
            case 'int':
                return h(PanelRow, {},
                    h(RangeControl, {
                        label: field.label || key,
                        value: toInt(val),
                        min: field.min ?? 0,
                        max: field.max ?? 100,
                        step: field.step ?? 1,
                        onChange: (next) => editMeta({ [key]: toInt(next) }),
                    })
                );

            case 'select':
                return h(PanelRow, {},
                    h(SelectControl, {
                        label: field.label || key,
                        value: (val ?? ''),
                        options: (field.options || []).map(o => ({ label: o.label, value: o.value })),
                        onChange: (next) => editMeta({ [key]: next }),
                    })
                );

            case 'text':
                return h(PanelRow, {},
                    h(TextControl, {
                        label: field.label || key,
                        value: val ?? '',
                        onChange: (next) => editMeta({ [key]: next }),
                    })
                );

            default:
                return h('div', { style: { opacity: .7, fontStyle: 'italic' } }, `Unbekannter Feldtyp: ${field.type}`);
        }
    }

    function SectionPanel({ section }) {
        const meta = useSelect((select) => select('core/editor').getEditedPostAttribute('meta') || {}, []);
        const { editPost } = useDispatch('core/editor');
        const editMeta = (patch) => editPost({ meta: { ...meta, ...patch } });

        const fields = section.fields || [];
        return h(PluginDocumentSettingPanel, { name: section.id, title: section.title, className: 'pam-section' },
            h(Fragment, {},
                fields.length
                    ? fields.map(f => h(FieldControl, { key: f.key, field: f, meta, editMeta }))
                    : h(Notice, { status: 'info', isDismissible: false }, 'Keine Felder konfiguriert.')
            )
        );
    }

    function PAMSidebar() {
        const postType = useSelect((s) => s('core/editor').getCurrentPostType(), []);
        const meta = useSelect((s) => s('core/editor').getEditedPostAttribute('meta') || {}, []);
        console.log('[PAM] sidebar render', { postType, meta });

        // Falls keine Sections geliefert wurden, zeige ein klar sichtbares Testpanel
        if (!SECTIONS.length) {
            return h(PluginDocumentSettingPanel, { name: 'pam_test', title: 'PAM Testpanel' },
                h('div', null,
                    'PAM_SECTIONS ist leer oder nicht gesetzt. ',
                    'Prüfe wp_localize_script() → Handle "owww-sidebar".'
                )
            );
        }

        return h(Fragment, {}, SECTIONS.map(s => h(SectionPanel, { key: s.id, section: s })));
    }

    registerPlugin('pam-sidebar-panels', { render: PAMSidebar, icon: null });
})(window.wp);
