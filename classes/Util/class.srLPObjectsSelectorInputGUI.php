<?php

/**
 * Class srLPObjectsSelectorInputGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy srLPObjectsSelectorInputGUI: ilFormPropertyDispatchGUI
 */
class srLPObjectsSelectorInputGUI extends ilRepositorySelector2InputGUI {


	/**
	 * srLPObjectsSelectorInputGUI constructor.
	 */
	public function __construct($a_title = "", $a_postvar = "") {
		parent::__construct($a_title, $a_postvar);
		$this->explorer_gui = new srLPObjectsSelectorExplorer([srCertificateDefinitionGUI::class, srCertParticipationCertificateFormGUI::class,srLPObjectsSelectorInputGUI::class],
			$this->getExplHandleCmd(), $this, "selectRepositoryItem", "root_id", "rep_exp_sel_".$a_postvar);
	}


}