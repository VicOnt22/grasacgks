/**
 * @module direction/directionediting
 */

import {Plugin} from 'ckeditor5/src/core';
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
    const editor = this.editor;
    const schema = editor.model.schema;

    // Allow direction attribute on all blocks.
    schema.extend( '$block', { allowAttributes: 'direction' } );
    editor.model.schema.setAttributeProperties( 'direction', { isFormatting: true } );

    editor.conversion.for('downcast').attributeToAttribute(
      {
        model: {
          key: 'direction',
          values: ['rtl', 'ltr']
        },
        view: {
          rtl: {
            key: 'dir',
            value: 'rtl'
          },
          ltr: {
            key: 'dir',
            value: 'ltr'
          }
        }
      }
    );

    editor.conversion.for('upcast').attributeToAttribute({
      view: {
        key: 'dir',
        value: 'rtl'
      },
      model: {
        key: 'direction',
        value: 'rtl'
      }
    });

    editor.commands.add( 'changeDirection', new DirectionCommand( editor ) );
  }
}
