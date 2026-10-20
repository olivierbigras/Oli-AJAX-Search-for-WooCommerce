(function (blocks, element, blockEditor, components, i18n) {
	var registerBlockType = blocks.registerBlockType;
	var el = element.createElement;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;
	var __ = i18n.__;

	registerBlockType('oli-ajax-search/search-form', {
		edit: function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps({ className: 'oli-as-block-preview' });

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __('Search form', 'oli-ajax-search') },
						el(SelectControl, {
							label: __('Layout', 'oli-ajax-search'),
							value: attributes.layout,
							options: [
								{ label: __('Classic bar', 'oli-ajax-search'), value: 'classic' },
								{ label: __('Icon that expands', 'oli-ajax-search'), value: 'icon' }
							],
							onChange: function (value) {
								setAttributes({ layout: value });
							}
						}),
						el(TextControl, {
							label: __('Placeholder', 'oli-ajax-search'),
							value: attributes.placeholder,
							onChange: function (value) {
								setAttributes({ placeholder: value });
							}
						})
					)
				),
				el('p', null, __('Oli AJAX Search form', 'oli-ajax-search') + ' — ' + attributes.layout)
			);
		},
		save: function () {
			return null;
		}
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n
);
