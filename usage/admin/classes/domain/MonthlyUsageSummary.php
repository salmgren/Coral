<?php

/*
**************************************************************************************************************************
** CORAL Usage Statistics Module
**
** Copyright (c) 2010 University of Notre Dame
**
** This file is part of CORAL.
**
** CORAL is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
**
** CORAL is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License along with CORAL.  If not, see <http://www.gnu.org/licenses/>.
**
**************************************************************************************************************************
*/

class MonthlyUsageSummary extends DatabaseObject {

	protected function defineRelationships() {}

	protected function overridePrimaryKeyName() {}

	protected function defineAttributes() {
		$this->addAttribute('monthlyUsageSummaryID');
		$this->addAttribute('titleID');
		$this->addAttribute('publisherPlatformID');
		$this->addAttribute('year');
		$this->addAttribute('month');
		$this->addAttribute('archiveInd');
		$this->addAttribute('usageCount');
		$this->addAttribute('overrideUsageCount');
		$this->addAttribute('outlierID');
		$this->addAttribute('ignoreOutlierInd');
		$this->addAttribute('mergeInd');
        $this->addAttribute('activityType');
        $this->addAttribute('sectionType');
	}

}

?>
