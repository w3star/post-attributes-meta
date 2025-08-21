(function (wp) {
    if (!wp || !wp.blocks) return;
    const { registerBlockType } = wp.blocks;
    const { useBlockProps } = wp.blockEditor;
    registerBlockType('pam/explorer', {
        title: 'Zusatzinfos – Explorer (Dual Sliders)',
        icon: 'filter',
        category: 'widgets',
        supports: { html: false, align: ['wide', 'full'] },
        edit: () => wp.element.createElement('div', useBlockProps({ className: 'pam-explorer' }), 'Explorer – Dual Sliders (Serverseitig) • Sortierung • Pagination'),
        save: () => null
    });

})(window.wp || {});