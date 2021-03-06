<?php
/*
 *
 * $Id$
 */

  uses(
    'org.dia.DiaElement'
  );

  /**
   * Represents a 'dia:color' node
   */
  class DiaColor extends DiaElement {

    public
      $node_name= 'dia:color';

    /**
     * Return XML representation of DiaComposite
     *
     * @return  &xml.Node
     */
    public function getNode() {
      $node= parent::getNode();
      if (isset($this->value)) 
        $node->setAttribute('val', $this->value);
      return $node;
    }

  }
?>
