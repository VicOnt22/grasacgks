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
    const { editor } = this;
    const { locale } = editor;
    const firstBlock = first(
      this.editor.model.document.selection.getSelectedBlocks(),
    );
    const defaultRtl = !!editor.config.get('direction.rtlDefault');
    // As first check whether to enable or disable the command as the value will always be false if the command cannot be enabled.
    this.isEnabled = !!firstBlock && this._canBeAligned(firstBlock);

    /**
     * A value of the current block's direction.
     *
     * @observable
     * @readonly
     * @member {String} #value
     */
    if ( this.isEnabled ) {
      if (firstBlock.hasAttribute( 'direction' )) {
        this.value = firstBlock.getAttribute('direction') !== locale.contentLanguageDirection;
      } else {
        this.value = false;
      }
    } else {
      this.value = false;
    }


    // Maybe a better solution as my ckeditor5 skills are minimal at best. But
    // if the default is to be RTL and the page just loaded found we need to
    // execute least once.  But we only want to do it once in the refresh to
    // avoid issues when the button is pressed.
    if ( defaultRtl && INIT ) {
      INIT = false;
      if (!firstBlock.hasAttribute( 'direction' )) {
        this.execute();
      }
    }
  }

  /**
   * Executes the command.
   *
   * If the currently selected block's direction attribute is not set or matches
   * the default content direction, the block's direction will be changed to be
   * opposite the default direction.
   *
   * If the currently selected block's direction attribute is opposite the
   * default content direction, the block's direction attribute will be removed.
   */
  execute() {
    const { editor } = this;
    const { model } = editor;
    const doc = model.document;
    const newDirection =
      this.editor.locale.contentLanguageDirection === 'rtl' ? 'ltr' : 'rtl';

    model.change((writer) => {
      // Get only those blocks from selected that can have direction set
      const blocks = Array.from(doc.selection.getSelectedBlocks()).filter(
        (block) => this._canBeAligned(block),
      );
      const currentDirection = blocks[0].getAttribute('direction');

      const removeDirection = currentDirection === newDirection;
      if ( removeDirection ) {
        removeDirectionFromSelection( blocks, writer );
      } else {
        // eslint-disable-next-line no-use-before-define
        setDirectionOnSelection(blocks, writer, newDirection);
      }
    });
  }

  /**
   * Checks whether a block can have direction set.
   *
   * @private
   *
   * @param {Element} block
   *   The block to check.
   * @return {Boolean}
   *   If block can be aligned.
   */
  _canBeAligned(block) {
    return this.editor.model.schema.checkAttribute(block, DIRECTION);
  }
}

/**
 * Removes the direction attribute from blocks.
 *
 * @private
 *
 * @param {Element} blocks
 *   The blocks to remove direction.
 * @param {Element} writer
 *   The writer.
 */
function removeDirectionFromSelection(blocks, writer) {
  // eslint-disable-next-line no-restricted-syntax
  for (const block of blocks) {
    writer.removeAttribute(DIRECTION, block);
  }
}

/**
 * Sets the direction attribute on blocks.
 *
 * @private
 *
 * @param {Element} blocks
 *   The blocks to set.
 * @param {Element} writer
 *   The writer.
 * @param {String} direction
 *   The direction to set to.
 */
function setDirectionOnSelection(blocks, writer, direction) {
  // eslint-disable-next-line no-restricted-syntax
  for (const block of blocks) {
    writer.setAttribute(DIRECTION, direction, block);
  }
}
