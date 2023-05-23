/**
 * @module direction/directioncommand
 */
import { Command } from 'ckeditor5/src/core';
import { first } from 'ckeditor5/src/utils';

const DIRECTION = 'direction';

let INIT = true;

/**
 * The direction command plugin.
 */
export default class DirectionCommand extends Command {

  /**
   * @inheritDoc
   */
  refresh() {
    const editor = this.editor;
    const locale = editor.locale;
    const firstBlock = first( this.editor.model.document.selection.getSelectedBlocks() );
    const defaultRtl = !!editor.config.get('direction.rtlDefault');

    // As first check whether to enable or disable the command as the value will always be false if the command cannot be enabled.
    this.isEnabled = !!firstBlock && this._canBeAligned( firstBlock );

    if ( defaultRtl && INIT ) {
      INIT = false;
      this.execute();
    }

    /**
     * A value of the current block's direction.
     *
     * @observable
     * @readonly
     * @member {String} #value
     */
    if ( this.isEnabled && firstBlock.hasAttribute( 'direction' ) ) {
      this.value = firstBlock.getAttribute( 'direction' );
    } else {
      this.value = locale.contentLanguageDirection === 'rtl' ? 'rtl' : 'ltr';
    }
  }

  /**
   * Executes the command. Applies the direction `value` to the selected
   * blocks.
   * If no `value` is passed, the `value` is the default one or it is equal to
   * the currently selected block's direction attribute, the command will
   * remove the direction from the selected blocks.
   */
  execute() {
    const editor = this.editor;
    const model = editor.model;
    const doc = model.document;
    const value = 'rtl';

    model.change( writer => {
      // Get only those blocks from selected that can have direction set
      const blocks = Array.from( doc.selection.getSelectedBlocks() ).filter( block => this._canBeAligned( block ) );
      const currentDirection = blocks[ 0 ].getAttribute( 'direction' );

      const removeDirection = value === 'ltr' || currentDirection === value || !value;

      if ( removeDirection ) {
        removeDirectionFromSelection( blocks, writer );
      } else {
        setDirectionOnSelection( blocks, writer, value );
      }
    } );
  }

	/**
	 * Checks whether a block can have direction set.
	 *
	 * @private
	 * @param {Element} block The block to be checked.
	 * @returns {Boolean}
	 */
	_canBeAligned( block ) {
		return this.editor.model.schema.checkAttribute( block, DIRECTION );
	}
}

// Removes the direction attribute from blocks.
// @private
function removeDirectionFromSelection( blocks, writer ) {
  for ( const block of blocks ) {
    writer.removeAttribute( DIRECTION, block );
  }
}

// Sets the direction attribute on blocks.
// @private
function setDirectionOnSelection( blocks, writer, direction ) {
  for ( const block of blocks ) {
    writer.setAttribute( DIRECTION, direction, block );
  }
}
