/**
 * @module direction/directionediting
 */

import { Plugin } from 'ckeditor5/src/core';
import DirectionCommand from './directioncommand';

/**
 * The direction editing feature. It introduces the
 * {@link DirectionCommand command} and adds the `direction` attribute for
 * block elements.
 */
export default class DirectionEditing extends Plugin {
  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'DirectionEditing';
  }

  /**
   * @inheritDoc
   */
  init() {
    const { editor } = this;
    const { schema } = editor.model;

    // Allow direction attribute on all blocks.
    schema.extend('$block', { allowAttributes: 'direction' });
    editor.model.schema.setAttributeProperties('direction', {
      isFormatting: true,
    });

    editor.conversion.attributeToAttribute({
      model: {
        key: 'direction',
        values: ['rtl', 'ltr'],
      },
      view: {
        rtl: {
          key: 'dir',
          value: 'rtl',
        },
        ltr: {
          key: 'dir',
          value: 'ltr',
        },
      },
    });

    editor.commands.add('changeDirection', new DirectionCommand(editor));
  }
}
