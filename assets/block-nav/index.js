(function (blocks, element, blockEditor, components, i18n) {
	var registerBlockType = blocks.registerBlockType;
	var el = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var __ = i18n.__;

	registerBlockType('oli-ajax-search/search-nav', {
		edit: function (props) {
			var blockProps = useBlockProps({ className: 'oli-as-block-preview' });
			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __('Search bar', 'oli-ajax-search') },
						el(SelectControl, {
							label: __('Layout', 'oli-ajax-search'),
							value: props.attributes.layout,
							options: [
								{ label: __('Search bar', 'oli-ajax-search'), value: 'classic' },
								{ label: __('Search icon', 'oli-ajax-search'), value: 'icon' }
							],
							onChange: function (value) {
								props.setAttributes({ layout: value });
							}
						})
					)
				),
				el('p', null, __('Oli AJAX Search navigation item', 'oli-ajax-search'))
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n);
