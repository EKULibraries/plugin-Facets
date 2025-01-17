<?php
	/**
	 * Helper to display a Facets Block
	 */

	/**
	 * Return list of objects associated to extra parameters (public, featured).
	 *
	 * @param subsetSQL
	 * @return objects
	 */
	function getObjectsForExtra($recordType, $subsetSQL, $extraType, $sortOrder) {
		// Create Where clause
		$whereSubset = createWhereSubsetClause($recordType, $subsetSQL);

		// Define Order By clause
		if ($sortOrder == 'count_alpha') {
			$orderBy = 'count DESC, ' . $extraType . ' DESC';
		} else {
			$orderBy = $extraType . ' DESC';
		}

		// Get the database.
		$db = get_db();
		// Get the table.
		$table = $db->getTable($recordType);
		// Build the select query.
		$subQuery = $table->getSelect();
		$subQuery->where($whereSubset);

		$select = "SELECT `" . $extraType . "`, COUNT(id) AS count FROM (" . $subQuery . ") foo GROUP BY `" . $extraType . "` ORDER BY " . $orderBy;

		return $table->fetchObjects($select);
	}

	/**
	 * Return HTML Select associated with Array of facets extra parameter values.
	 *
	 * @param recordType
	 * @param subsetSQL
	 * @param extraType
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @return html.
	 */
	function getFacetSelectForExtra($recordType, $subsetSQL, $extraType, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false) {
		if ($extras = getObjectsForExtra($recordType, $subsetSQL, $extraType, $sortOrder)) {
			// Build array
			$facetExtras = buildExtrasArray($extras, $extraType);

			// Stores data for selected extra, if any
			$selectedExtra = getSelectedExtra($facetExtras, $extraType);

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetExtras, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetExtras = array_filter($facetExtras, "isNotSingleExtra");
			}

			$addOptions = false;
			// Build first part of the select tag
			if (isset($selectedExtra)) {
				$url = getFieldUrl($recordType, $extraType, null);
				$html  = "<div class=\"select-cross\"><select class=\"facet-selected\" name=\"tag\">";
				$html .= "<option value=\"\" data-url=\"" . $url . "\"> " . html_escape(__('Remove filter')) . "...</option>";
				$html .= "<option selected value=\"\">" . __(getExtraName($extraType, $selectedExtra)) . "</option>";
			} elseif (count($facetExtras) > 0) {
				$html  = "<div class=\"select-arrow\"><select class=\"facet\" name=\"" . $extraType . "\">";
				$html .= "<option value=\"\">" . html_escape(__('Select')) . "...</option>";
				$addOptions = true;
			}

			// Build additional part of the select tag (if needed)
			if ($addOptions) {
				foreach ($facetExtras as $value => $count) {
					$url = getFieldUrl($recordType, $extraType, $value);
					$html .= "<option value=\"" . $value . "\" data-url=\"" . $url . "\">" . __(getExtraName($extraType, $value)) . ($showPopularity ? " (" . $count . ")" : "") . "</option>";
				}
			}
			$html .= "</select></div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return HTML Checkboxes associated with Array of facets extra parametervalues.
	 *
	 * @param recordType
	 * @param subsetSQL
	 * @param extraType
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @param limitCheckboxes
	 * @return html
	 */
	function getFacetCheckboxesForExtra($recordType, $subsetSQL, $extraType, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false, $limitCheckboxes = 0) {
		if ($extras = getObjectsForExtra($recordType, $subsetSQL, $extraType, $sortOrder)) {
			// Build array
			$facetExtras = buildExtrasArray($extras, $extraType);

			// Stores data for selected extra, if any
			$selectedExtra = getSelectedExtra($facetExtras, $extraType);

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetExtras, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetExtras = array_filter($facetExtras, "isNotSingleExtra");
			}

			$countCheckboxes = 0;
			$html = '<div>';
			// Build first part of the checkboxes tag
			if (isset($selectedExtra)) {
				$url = getFieldUrl($recordType, $extraType, null, $selectedExtra);
				$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"\" data-url=\"" . $url . "\" checked><label><strong>" . html_escape(__(getExtraName($extraType, $selectedExtra))) . "</strong></label></div>";
				$countCheckboxes++;
			}

			$hidingSeparator = false;
			// Build additional part of the select tag (if needed)
			foreach ($facetExtras as $value => $count) {
				if (!isset($selectedExtra) || $value <> $selectedExtra) {
					if ($limitCheckboxes != 0 && $countCheckboxes >= $limitCheckboxes && !$hidingSeparator) {
						// Add link to show other values
						$html .= "<div class=\"hidden\" id=\"facet-extra-values-extras\">";
						$hidingSeparator = true;
					}

					$url = getFieldUrl($recordType, $extraType, $value);
					$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"" . $value . "\" data-url=\"" . $url . "\"><label>" . html_escape(__(getExtraName($extraType, $value))) . ($showPopularity ? "<span class=\"facet-checkbox-count\"> (" . $count . ")</span>" : "") . "</label></div>";
					$countCheckboxes++;
				}
			}

			if ($hidingSeparator) {
				$html .= "</div>";
				$html .= "<a id=\"facet-extra-link-extras\" class=\"facet-visibility-toggle\" data-element-id=\"extras\">" . __('show more') . "</a>";
			}

			$html .= "</div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return list of objects associated to extra parameters (user, public, featured).
	 *
	 * @param subsetSQL
	 * @return objects
	 */
	function getObjectsForUser($recordType, $subsetSQL, $sortOrder) {
		// Create Where clause
		$whereSubset = createWhereSubsetClause($recordType, $subsetSQL);

		// Define Order By clause
		if ($sortOrder == 'count_alpha') {
			$orderBy = array('count DESC', 'name ASC');
		} else {
			$orderBy = array('name ASC');
		}

		// Get the database.
		$db = get_db();
		// Get the table.
		$table = $db->getTable('User');
		// Build the select query.
		$select = $table->getSelect();
		$select->columns('COUNT(users.id) AS count');
		if ($recordType == 'item') {
			$select->joinInner(array('items' => $db->Item), 'users.id = items.owner_id', array());
		} else {
			$select->joinInner(array('collections' => $db->Collection), 'users.id = collections.owner_id', array());
		}
		$select->where($whereSubset);
		$select->group('users.id');
		$select->order($orderBy);

		return $table->fetchObjects($select);
	}

	/**
	 * Return HTML Select associated with Array of facets extra parameter values.
	 *
	 * @param subsetSQL
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @return html.
	 */
	function getFacetSelectForUser($recordType, $subsetSQL, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false) {
		if ($users = getObjectsForUser($recordType, $subsetSQL, $sortOrder)) {
			// Build array
			$facetUsers = buildUsersArray($users);

			// Stores data for selected user, if any
			$selectedUser = getSelectedUser($facetUsers);

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetUsers, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetUsers = array_filter($facetUsers, "isNotSingleExtra");
			}

			$addOptions = false;
			// Build first part of the select tag
			if (isset($selectedUser)) {
				$url = getFieldUrl($recordType, 'user', null);
				$html  = "<div class=\"select-cross\"><select class=\"facet-selected\" name=\"tag\">";
				$html .= "<option value=\"\" data-url=\"" . $url . "\"> " . html_escape(__('Remove filter')) . "...</option>";
				$html .= "<option selected value=\"\">" . trim($selectedUser['name']) . "</option>";
			} elseif (count($facetUsers) > 0) {
				$html  = "<div class=\"select-arrow\"><select class=\"facet\" name=\"user\">";
				$html .= "<option value=\"\">" . html_escape(__('Select')) . "...</option>";
				$addOptions = true;
			}

			// Build additional part of the select tag (if needed)
			if ($addOptions) {
				foreach ($facetUsers as $user) {
					$url = getFieldUrl($recordType, 'user', $user['id']);
					$html .= "<option value=\"" . $user['id'] . "\" data-url=\"" . $url . "\">" . trim($user['name']) . ($showPopularity ? " (" . $user['count'] . ")" : "") . "</option>";
				}
			}
			$html .= "</select></div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return HTML Checkboxes associated with Array of facets values.
	 *
	 * @param subsetSQL
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @param limitCheckboxes
	 * @return html
	 */
	function getFacetCheckboxesForUser($recordType, $subsetSQL, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false, $limitCheckboxes = 0) {
		if ($users = getObjectsForUser($recordType, $subsetSQL, $sortOrder)) {
			// Build array
			$facetUsers = buildUsersArray($users);

			// Store data for selected user, if any
			$selectedUser = getSelectedUser($facetUsers);

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetUsers, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetUsers = array_filter($facetUsers, "isNotSingleExtra");
			}

			$countCheckboxes = 0;
			$html = '<div>';
			// Build first part of the checkboxes tag
			if (isset($selectedUser)) {
				$url = getFieldUrl($recordType, 'user', null, $selectedUser);
				$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"\" data-url=\"" . $url . "\" checked><label><strong>" . trim(html_escape($selectedUser['name'])) . "</strong></label></div>";
				$countCheckboxes++;
			}

			$hidingSeparator = false;
			// Build additional part of the select tag (if needed)
			foreach ($facetUsers as $user) {
				if ($user != $selectedUser) {
					if ($limitCheckboxes != 0 && $countCheckboxes >= $limitCheckboxes && !$hidingSeparator) {
						// Add link to show other values
						$html .= "<div class=\"hidden\" id=\"facet-extra-values-users\">";
						$hidingSeparator = true;
					}

					$url = getFieldUrl($recordType, 'user', $user['id']);
					$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"" . trim($user['name']) . "\" data-url=\"" . $url . "\"><label>" . trim(html_escape($user['name'])) . ($showPopularity ? "<span class=\"facet-checkbox-count\"> (" . $user['count'] . ")</span>" : "") . "</label></div>";
					$countCheckboxes++;
				}
			}

			if ($hidingSeparator) {
				$html .= "</div>";
				$html .= "<a id=\"facet-extra-link-users\" class=\"facet-visibility-toggle\" data-element-id=\"users\">" . __('show more') . "</a>";
			}

			$html .= "</div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return list of objects associated to tag.
	 *
	 * @param subsetSQL
	 * @return objects
	 */
	function getObjectsForTag($subsetSQL, $sortOrder) {
		// Create Where clause
		$whereSubset = createWhereSubsetClause('item', $subsetSQL);

		// Define Order By clause
		if ($sortOrder == 'count_alpha') {
			$orderBy = array('tagCount DESC', 'name ASC');
		} else {
			$orderBy = array('name ASC');
		}

		// Get the database.
		$db = get_db();
		// Get the table.
		$table = $db->getTable('Tag');
		// Build the select query.
		$select = $table->getSelectForFindBy();
		$table->filterByTagType($select, 'Item');
		$select->where($whereSubset);
		$select->order($orderBy);

		return $table->fetchObjects($select);
	}

	/**
	 * Return HTML Select associated with Array of facets tag values.
	 *
	 * @param subsetSQL
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @return html.
	 */
	function getFacetSelectForTag($subsetSQL, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false) {
		if ($tags = getObjectsForTag($subsetSQL, $sortOrder)) {
			// Build array
			$facetTags = buildTagsArray($tags);

			// Stores data for selected tag, if any
			$selectedTagName = (isset($_GET['tags']) ? $_GET['tags'] : '');

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetTags, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetTags = array_filter($facetTags, "isNotSingleExtra");
			}

			$addOptions = false;
			// Build first part of the select tag
			if ($selectedTagName != '') {
				$url = getFieldUrl('item', 'tags', null);
				$html  = "<div class=\"select-cross\"><select class=\"facet-selected\" name=\"tag\">";
				$html .= "<option value=\"\" data-url=\"" . $url . "\"> " . html_escape(__('Remove filter')) . "...</option>";
				$html .= "<option selected value=\"\">" . $selectedTagName . "</option>";
			} elseif (count($facetTags) > 0) {
				$html  = "<div class=\"select-arrow\"><select class=\"facet\" name=\"tag\">";
				$html .= "<option value=\"\">" . html_escape(__('Select')) . "...</option>";
				$addOptions = true;
			}

			// Build additional part of the select tag (if needed)
			if ($addOptions) {
				foreach ($facetTags as $tag) {
					$url = getFieldUrl('item', 'tags', $tag['name']);
					$html .= "<option value=\"" . $tag['id'] . "\" data-url=\"" . $url . "\">" . trim($tag['name']) . ($showPopularity ? " (" . $tag['count'] . ")" : "") . "</option>";
				}
			}
			$html .= "</select></div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return HTML Checkboxes associated with Array of facets tag values.
	 *
	 * @param subsetSQL
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @return html.
	 */
	function getFacetCheckboxesForTag($subsetSQL, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false, $limitCheckboxes = 0) {
		if ($tags = getObjectsForTag($subsetSQL, $sortOrder)) {
			// Build array
			$facetTags = buildTagsArray($tags);

			// Stores data for selected tags, if any
			$selectedTags = (isset($_GET['tags']) ? $_GET['tags'] : '');
			$selectedTagNames = explode(option('tag_delimiter'), $selectedTags);

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetTags, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetTags = array_filter($facetTags, "isNotSingleExtra");
			}

			$countCheckboxes = 0;
			$html = '<div>';
			// Build first part of the checkboxes tag
			if ($selectedTags != '') {
				$selectedTagNames = explode(option('tag_delimiter'), $selectedTags);
				foreach ($selectedTagNames as $selectedTagName) {
					$url = getFieldUrl('item', 'tags', null, $selectedTagName);
					$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"\" data-url=\"" . $url . "\" checked><label><strong>" . trim(html_escape($selectedTagName)) . "</strong></label></div>";
					$countCheckboxes++;
				}
			}

			$hidingSeparator = false;
			// Build additional part of the select tag (if needed)
			foreach ($facetTags as $tag) {
				if (!in_array($tag['name'], $selectedTagNames)) {
					if ($limitCheckboxes != 0 && $countCheckboxes >= $limitCheckboxes && !$hidingSeparator) {
						// Add link to show other values
						$html .= "<div class=\"hidden\" id=\"facet-extra-values-tags\">";
						$hidingSeparator = true;
					}

					$url = getFieldUrl('item', 'tags', $tag['name']);
					$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"" . trim($tag['name']) . "\" data-url=\"" . $url . "\"><label>" . trim(html_escape($tag['name'])) . ($showPopularity ? "<span class=\"facet-checkbox-count\"> (" . $tag['count'] . ")</span>" : "") . "</label></div>";
					$countCheckboxes++;
				}
			}

			if ($hidingSeparator) {
				$html .= "</div>";
				$html .= "<a id=\"facet-extra-link-tags\" class=\"facet-visibility-toggle\" data-element-id=\"tags\">" . __('show more') . "</a>";
			}

			$html .= "</div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return list of objects associated to collection.
	 *
	 * @param subsetSQL
	 * @return objects
	 */
	function getObjectsForCollection($subsetSQL) {
		// Create Where clause
		$whereSubset = createWhereSubsetClause('item', $subsetSQL);

		// Get the database.
		$db = get_db();
		// Get the table.
		$table = $db->getTable('Collection');
		// Build the select query.
		$select = $table->getSelect()
			->columns('COUNT(collections.id) AS count')
			->joinInner(array('items' => $db->Items), 'collections.id = items.collection_id', array())
			->where($whereSubset)
			->group('collections.id');

		return $table->fetchObjects($select);
	}

	/**
	 * Return HTML Select associated with Array of facets collection values.
	 *
	 * @param subsetSQL
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @return html.
	 */
	function getFacetSelectForCollection($subsetSQL, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false) {
		if ($collections = getObjectsForCollection($subsetSQL, $sortOrder)) {
			// Build array
			$facetCollections = buildCollectionsArray($collections);

			// Store data for selected collection, if any
			$selectedCollection = getSelectedCollection($facetCollections);

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetCollections, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetCollections = array_filter($facetCollections, "isNotSingleExtra");
			}

			// Sort array (have to do it now instead than in select because of the way we get the Collection name)
			$facetCollections = sortCollections($facetCollections, $sortOrder);

			$addOptions = false;
			// Build first part of the select tag
			if (isset($selectedCollection)) {
				$url = getFieldUrl('item', 'collection', null);
				$html  = "<div class=\"select-cross\"><select class=\"facet-selected\" name=\"collection\">";
				$html .= "<option value=\"\" data-url=\"" . $url . "\"> " . html_escape(__('Remove filter')) . "...</option>";
				$html .= "<option selected value=\"\">" . trim($selectedCollection['name']) . "</option>";
			} elseif (count($facetCollections) > 0) {
				$html  = "<div class=\"select-arrow\"><select class=\"facet\" name=\"collection\">";
				$html .= "<option value=\"\">" . html_escape(__('Select')) . "...</option>";
				$addOptions = true;
			}

			// Build additional part of the select tag (if needed)
			if ($addOptions) {
				foreach ($facetCollections as $collection) {
					$url = getFieldUrl('item', 'collection', $collection['id']);
					$html .= "<option value=\"" . $collection['id'] . "\" data-url=\"" . $url . "\">" . trim($collection['name']) . ($showPopularity ? " (" . $collection['count'] . ")" : "") . "</option>";
				}
			}
			$html .= "</select></div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return HTML Checkboxes associated with Array of facets values.
	 *
	 * @param subsetSQL
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @param limitCheckboxes
	 * @return html
	 */
	function getFacetCheckboxesForCollection($subsetSQL, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false, $limitCheckboxes = 0) {
		if ($collections = getObjectsForCollection($subsetSQL, $sortOrder)) {
			// Build array
			$facetCollections = buildCollectionsArray($collections);

			// Store data for selected collection, if any
			$selectedCollection = getSelectedCollection($facetCollections);

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetCollections, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetCollections = array_filter($facetCollections, "isNotSingleExtra");
			}

			// Sort array (have to do it now instead than in select because of the way we get the Collection's name)
			$facetCollections = sortCollections($facetCollections, $sortOrder);

			$countCheckboxes = 0;
			$html = '<div>';
			// Build first part of the checkboxes tag
			if (isset($selectedCollection)) {
				$url = getFieldUrl('item', 'collection', null);
				$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"\" data-url=\"" . $url . "\" checked><label><strong>" . trim(html_escape($selectedCollection['name'])) . "</strong></label></div>";
				$countCheckboxes++;
			}

			$hidingSeparator = false;
			// Build additional part of the select tag (if needed)
			foreach ($facetCollections as $collection) {
				if ($collection != $selectedCollection) {
					if ($limitCheckboxes != 0 && $countCheckboxes >= $limitCheckboxes && !$hidingSeparator) {
						// Add link to show other values
						$html .= "<div class=\"hidden\" id=\"facet-extra-values-collections\">";
						$hidingSeparator = true;
					}

					$url = getFieldUrl('item', 'collection', $collection['id']);
					$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"" . trim($collection['name']) . "\" data-url=\"" . $url . "\"><label>" . trim(html_escape($collection['name'])) . ($showPopularity ? "<span class=\"facet-checkbox-count\"> (" . $collection['count'] . ")</span>" : "") . "</label></div>";
					$countCheckboxes++;
				}
			}

			if ($hidingSeparator) {
				$html .= "</div>";
				$html .= "<a id=\"facet-extra-link-collections\" class=\"facet-visibility-toggle\" data-element-id=\"collections\">" . __('show more') . "</a>";
			}

			$html .= "</div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return list of objects associated to collection.
	 *
	 * @param subsetSQL
	 * @param sortOrder
	 * @return objects
	 */
	function getObjectsForItemType($subsetSQL, $sortOrder) {
		// Create Where Subset clause
		$whereSubset = createWhereSubsetClause('item', $subsetSQL);

		// Define Order by clause
		if ($sortOrder == 'count_alpha') {
			$orderBy = array('count DESC', 'name ASC');
		} else {
			$orderBy = array('name ASC');
		}

		// Get the database.
		$db = get_db();
		// Get the table.
		$table = $db->getTable('ItemType');
		// Build the select query.
		$select = $table->getSelect()
			->columns('COUNT(item_types.id) AS count')
			->joinInner(array('items' => $db->Items), 'item_types.id = items.item_type_id', array())
			->where($whereSubset)
			->group('item_types.id')
			->order($orderBy);

		return $table->fetchObjects($select);
	}

	/**
	 * Return HTML Select associated with Array of facets item type values.
	 *
	 * @param subsetSQL
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @return html.
	 */
	function getFacetSelectForItemType($subsetSQL, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false) {
		if ($itemTypes = getObjectsForItemType($subsetSQL, $sortOrder)) {
			// Build array
			$facetItemTypes = buildItemTypesArray($itemTypes);

			// Store data for selected item type, if any
			$selectedItemType = getSelectedItemType($facetItemTypes);

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetItemTypes, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetItemTypes = array_filter($facetItemTypes, "isNotSingleExtra");
			}

			$addOptions = false;
			// Build first part of the select tag
			if (!empty($selectedItemType)) {
				$url = getFieldUrl('item', 'type', null);
				$html  = "<div class=\"select-cross\"><select class=\"facet-selected\" name=\"type\">";
				$html .= "<option value=\"\" data-url=\"" . $url . "\"> " . html_escape(__('Remove filter')) . "...</option>";
				$html .= "<option selected value=\"\">" . trim($selectedItemType['name']) . "</option>";
			} elseif (count($facetItemTypes) > 0) {
				$html  = "<div class=\"select-arrow\"><select class=\"facet\" name=\"type\">";
				$html .= "<option value=\"\">" . html_escape(__('Select')) . "...</option>";
				$addOptions = true;
			}

			// Build additional part of the select tag (if needed)
			if ($addOptions) {
				foreach ($facetItemTypes as $itemType) {
					$url = getFieldUrl('item', 'type', $itemType['id']);
					$html .= "<option value=\"" . $itemType['id'] . "\" data-url=\"" . $url . "\">" . trim($itemType['name']) . ($showPopularity ? " (" . $itemType['count'] . ")" : "") . "</option>";
				}
			}
			$html .= "</select></div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return HTML Checkboxes associated with Array of facets item type values.
	 *
	 * @param subsetSQL
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @return html.
	 */
	function getFacetCheckboxesForItemType($subsetSQL, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false, $limitCheckboxes = 0) {
		if ($itemTypes = getObjectsForItemType($subsetSQL, $sortOrder)) {
			// Build array
			$facetItemTypes = buildItemTypesArray($itemTypes);

			// Store data for selected item type, if any
			$selectedItemType = getSelectedItemType($facetItemTypes);

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetItemTypes, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetItemTypes = array_filter($facetItemTypes, "isNotSingleExtra");
			}

			$countCheckboxes = 0;
			$html = '<div>';
			// Build first part of the checkboxes tag
			if (isset($selectedItemType)) {
				$url = getFieldUrl('item', 'type', null);
				$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"\" data-url=\"" . $url . "\" checked><label><strong>" . trim(html_escape($selectedItemType['name'])) . "</strong></label></div>";
				$countCheckboxes++;
			}

			$hidingSeparator = false;
			// Build additional part of the select tag (if needed)
			foreach ($facetItemTypes as $itemType) {
				if ($itemType != $selectedItemType) {
					if ($limitCheckboxes != 0 && $countCheckboxes >= $limitCheckboxes && !$hidingSeparator) {
						// Add link to show other values
						$html .= "<div class=\"hidden\" id=\"facet-extra-values-item-types\">";
						$hidingSeparator = true;
					}

					$url = getFieldUrl('item', 'type', $itemType['id']);
					$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"" . trim($itemType['name']) . "\" data-url=\"" . $url . "\"><label>" . trim(html_escape($itemType['name'])) . ($showPopularity ? "<span class=\"facet-checkbox-count\"> (" . $itemType['count'] . ")</span>" : "") . "</label></div>";
					$countCheckboxes++;
				}
			}

			if ($hidingSeparator) {
				$html .= "</div>";
				$html .= "<a id=\"facet-extra-link-item-types\" class=\"facet-visibility-toggle\" data-element-id=\"item-types\">" . __('show more') . "</a>";
			}

			$html .= "</div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return list of objects associated to element.
	 *
	 * @param recordType
	 * @param subsetSQL
	 * @param elementId
	 * @param isDate
	 * @param sortOrder
	 * @return objects
	 */
	function getObjectsForElement($recordType, $subsetSQL, $elementId, $isDate, $sortOrder) {
		// Create Where clauses
		$whereRecordType = createWhereRecordTypeClause($recordType);
		$whereSubset = createWhereSubsetClause($recordType, $subsetSQL);

		// Create the columns, groupBy and orderBy clauses
		if ($isDate) {
			$columns1 = array('element_texts.text AS year');
			$columns2 = 'COUNT(year) AS count';
			$groupBy = array('year', 'record_id');
			if ($sortOrder == 'count_alpha') {
				$orderBy = 'count DESC, year DESC';
			} else {
				$orderBy = 'year DESC';
			}
		} else {
			$columns1 = '';
			$columns2 = 'COUNT(text) AS count';
			$groupBy = array('text', 'record_id');
			if ($sortOrder == 'count_alpha') {
				$orderBy = 'count DESC, text ASC';
			} else {
				$orderBy = 'text ASC';
			}
		}

		// Get the database.
		$db = get_db();
		// Get the table.
		$table = $db->getTable('ElementText');
		// Build the select query.
		$subQuery = $table->getSelect();
		$subQuery->columns($columns1);
		$subQuery->joinInner(array('elements' => $db->Elements), 'element_texts.element_id = elements.id', array());
		$subQuery->joinInner(array('element_sets' => $db->ElementSet), 'element_sets.id = elements.element_set_id', array());
		if ($recordType == 'item') {
			$subQuery->joinInner(array('items' => $db->Item), 'items.id = element_texts.record_id', array());
		} else {
			$subQuery->joinInner(array('collections' => $db->Collection), 'collections.id = element_texts.record_id', array());
		}
		$subQuery->where('elements.id = '. $elementId);
		$subQuery->where($whereRecordType);
		$subQuery->where($whereSubset);
		$subQuery->group($groupBy);

		$select = "SELECT *, " . $columns2 . " FROM (" . $subQuery . ") foo GROUP BY `text` ORDER BY " . $orderBy;

		return $table->fetchObjects($select);
	}

	/**
	 * Return HTML Select associated with Array of facets values.
	 *
	 * @param recordType
	 * @param subsetSQL
	 * @param elementId
	 * @param isDate
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @return html
	 */
	function getFacetSelectForElement($recordType, $subsetSQL, $elementId = 50, $isDate = false, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false) {
		// Build array
		if ($elements = getObjectsForElement($recordType, $subsetSQL, $elementId, $isDate, $sortOrder)) {
			$facetElement = array();
			foreach ($elements as $element) {
				if ($isDate) {
					$facetElement[$element->year] = $element->count;
				} else {
					$facetElement[$element->text] = $element->count;
				}
			}
			$element_id = $element->element_id;

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetElement, 'isNotSingleElement')) > FACETS_MINIMUM_AMOUNT) {
				$facetElement = array_filter($facetElement, "isNotSingleElement");
			}

			// Get current parameters to check if one is selected
			if (!empty($_GET['advanced'])) {
				$search = $_GET['advanced'];
				foreach ($search as $searchIndex => $searchArray){
					if (isset($searchArray['element_id']) && $searchArray['element_id'] == $element_id) {
						$term = $searchArray['terms'];
						break;
					}
				}
			}

			$addOptions = false;
			// Build first part of the select tag
			if (isset($term)){
				$html =	"<div class=\"select-cross\"><select id=\"" . $element_id . "\" class=\"facet-selected\" name=\"" . $elementId . "\">";
				$url = getElementFieldUrl($recordType, $element_id, null, $isDate);
				$html .= "<option value=\"\" data-url=\"" . $url . "\"> " . html_escape(__('Remove filter')) . "...</option>";
				$html .= "<option selected value=\"\">" . trim($term) . "</option>";
			} elseif (count($facetElement) > 0) {
				$html =	"<div class=\"select-arrow\"><select id=\"" . $element_id . "\" class=\"facet\" name=\"" . $elementId . "\">";
				$html .= "<option value=\"\">" . html_escape(__('Select')) . "...</option>";
				$addOptions = true;
			}

			// Build additional part of the select tag (if needed)
			if ($addOptions) {
				foreach ($facetElement as $name => $count) {
					$url = getElementFieldUrl($recordType, $element_id, $name, $isDate);
					$html .= "<option value=\"" . trim($name) . "\" data-url=\"" . $url . "\">" . trim($name) . ($showPopularity ? " (" . $count . ")" : "") . "</option>";
				}
			}
			$html .= "</select></div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return HTML Checkboxes associated with Array of facets values.
	 *
	 * @param recordType
	 * @param subsetSQL
	 * @param elementId
	 * @param isDate
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @param limitCheckboxes
	 * @return html
	 */
	function getFacetCheckboxesForElement($recordType, $subsetSQL, $elementId = 50, $isDate = false, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false, $limitCheckboxes = 0) {
		// Build array
		if ($elements = getObjectsForElement($recordType, $subsetSQL, $elementId, $isDate, $sortOrder)) {
			$facetElement = array();
			foreach ($elements as $element) {
				if ($isDate) {
					$facetElement[$element->year] = $element->count;
				} else {
					$facetElement[$element->text] = $element->count;
				}
			}
			$element_id = $element->element_id;

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetElement, 'isNotSingleElement')) > FACETS_MINIMUM_AMOUNT) {
				$facetElement = array_filter($facetElement, "isNotSingleElement");
			}

			$selectedTerms = array();
			// Get current parameters to check if one or more are selected
			if (!empty($_GET['advanced'])) {
				$search = $_GET['advanced'];
				foreach ($search as $searchIndex => $searchArray){
					if (isset($searchArray['element_id']) && $searchArray['element_id'] == $element_id) {
						$selectedTerms[] = $searchArray['terms'];
					}
				}
			}

			$countCheckboxes = 0;
			$html = '<div>';
			// Build first part of the checkboxes tag
			if (!empty($selectedTerms)) {
				foreach ($selectedTerms as $term){
					$url = getElementFieldUrl($recordType, $element_id, null, $isDate, $term);
					$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"\" data-url=\"" . $url . "\" checked><label><strong>" . trim(html_escape($term)) . "</strong></label></div>";
					$countCheckboxes++;
				}
			}

			$hidingSeparator = false;
			// Build additional part of the select tag (if needed)
			foreach ($facetElement as $name => $count) {
				if (!in_array($name, $selectedTerms)) {
					if ($limitCheckboxes != 0 && $countCheckboxes >= $limitCheckboxes && !$hidingSeparator) {
						// Add link to show other values
						$html .= "<div class=\"hidden\" id=\"facet-extra-values-" . $elementId . "\">";
						$hidingSeparator = true;
					}

					$url = getElementFieldUrl($recordType, $element_id, $name, $isDate);
					$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"" . trim($name) . "\" data-url=\"" . $url . "\"><label>" . trim(html_escape($name)) . ($showPopularity ? "<span class=\"facet-checkbox-count\"> (" . $count . ")</span>" : "") . "</label></div>";
					$countCheckboxes++;
				}
			}

			if ($hidingSeparator) {
				$html .= "</div>";
				$html .= "<a id=\"facet-extra-link-" . $elementId . "\" class=\"facet-visibility-toggle\" data-element-id=\"" . $elementId . "\">" . __('show more') . "</a>";
			}

			$html .= "</div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Add an Element Field to Search to the current URL.
	 *
	 * @param string $field_id The Element id.
	 * @param string $value The Element value.
	 * @param string $isDate
	 * @param string $oldValue The Element old value.
	 * @return string The new URL.
	 */
	function getElementFieldUrl($recordType, $field_id, $value = null, $isDate = false, $oldValue = null)
	{
		// Get the current facets.
		if (!empty($_GET['advanced'])) {
			$search = $_GET['advanced'];
			if ($value == '') {
				if ($oldValue == '') {
					// unset current element filter(s) if already set
					foreach ($search as $searchIndex => $searchArray){
						if (isset($searchArray['element_id']) && $searchArray['element_id'] == $field_id){
							unset ($search[$searchIndex]);
						}
					}
				} else {
					// unset current element filter with specific value if already set
					foreach ($search as $searchIndex => $searchArray){
						if (isset($searchArray['element_id']) && $searchArray['element_id'] == $field_id && $searchArray['terms'] == $oldValue){
							unset ($search[$searchIndex]);
							break;
						}
					}
				}
			}
		} else {
			$search = array();
		}
		if (!is_null($value)) {
			if ($isDate) {
				$search[] = array('element_id'=>$field_id, 'type'=>'starts with', 'terms'=>$value);
			} else {
				$search[] = array('element_id'=>$field_id, 'type'=>'is exactly', 'terms'=>$value);
			}
		}

		// set previous parameters
		$params['advanced'] = $search;
		$params = setPreviousParameters($params);

		// return rebuilt route
		return getRebuiltUrl($recordType, $params);
	}

	/**
	 * Add an Element Field to Search to the current URL.
	 *
	 * @param string $filter The filter field name (tags|tag_id|type|collection).
	 * @param string $value The Element value.
	 * @param string $oldValue The Element old value.
	 * @return string The new URL.
	 */
	function getFieldUrl($recordType, $filter, $value = null, $oldValue = null)
	{
		// Get the current facets.
		if (!empty($_GET['advanced'])) {
			$search = $_GET['advanced'];
		} else {
			$search = array();
		}

		// set previous parameters
		$params['advanced'] = $search;
		$params = setPreviousParameters($params);

		// set(unset) current
		if (!is_null($value)){
			if ($filter != 'tags' || empty($params[$filter])) {
				$params[$filter] = $value;
			} else {
				$tags = explode(option('tag_delimiter'), $params[$filter]);
				array_push($tags, $value);
				$params[$filter] = implode(option('tag_delimiter'), $tags);
			}
		} else {
			if ($filter == 'tags' && $oldValue != '') {
				if (!empty($params[$filter])) {
					$tags = explode(option('tag_delimiter'), $params[$filter]);
					$tags = array_diff($tags, array($oldValue));
					$params[$filter] = implode(option('tag_delimiter'), $tags);
				}
				if (empty($params[$filter])) unset($params[$filter]);
			} else {
				unset($params[$filter]);
			}
		}

		// return rebuilt route
		return getRebuiltUrl($recordType, $params);
	}

	function isFacetActive($recordType, $element_name, $settings, $extra_name = null) {
		if ($extra_name != '') {
			if (isset($settings[$extra_name][$recordType])) {
				return ((bool)$settings[$extra_name][$recordType]);
			}
		} elseif (isset($settings['elements'][$element_name][$recordType])) {
			return ((bool)$settings['elements'][$element_name][$recordType]);
		}
		return false;
	}

	function isNotSingleElement($count) {
		if ($count > 1) return $count;
	}

	function isNotSingleExtra($element) {
		if ($element['count'] > 1) return $element;
	}

	function createWhereRecordTypeClause($recordType) {
		return 'element_texts.record_type = \'' . ucfirst($recordType) . '\'';
	}

	function createWhereSubsetClause($recordType, $sql) {
		if ($sql != '') {
			return $recordType . 's.id IN (' . $sql . ')';
		} else {
			return '1=1';
		}
	}

	function recordTypeActive($recordType, $elements) {
		if (isset($elements)) {
			foreach ($elements as $element) {
				if (array_key_exists($recordType, $element)) return true;
			}
		}

		return false;
	}

	function buildCollectionsArray($collections) {
		$facetCollections = array();
		foreach ($collections as $collection) {
			$facetCollections[$collection->id]['id'] = $collection->id;
			$facetCollections[$collection->id]['name'] = $collection->getDisplayTitle();
			$facetCollections[$collection->id]['count'] = $collection->count;
		}
		return $facetCollections;
	}

	function getSelectedCollection($collections) {
		if (isset($_GET['collection'])) {
			$collection_id = $_GET['collection'];
			if (array_key_exists($collection_id, $collections)) {
				return $collections[$collection_id];
			}
		}
	}

	function sortCollections($collections, $sortOrder = 'count_alpha') {
		if ($sortOrder == 'count_alpha') {
			array_multisort(array_column($collections, 'count'), SORT_DESC, array_column($collections, 'name'), SORT_ASC, $collections);
		} else {
			array_multisort(array_column($collections, 'name'), SORT_ASC, $collections);
		}
		return $collections;
	}

	function buildItemTypesArray($itemTypes) {
		$facetItemTypes = array();
		foreach ($itemTypes as $itemType) {
			$facetItemTypes[$itemType->id]['id'] = $itemType->id;
			$facetItemTypes[$itemType->id]['name'] = $itemType->name;
			$facetItemTypes[$itemType->id]['count'] = $itemType->count;
		}
		return $facetItemTypes;
	}

	function getSelectedItemType($itemTypes) {
		if (isset($_GET['type'])) {
			$itemType_id = $_GET['type'];
			if (array_key_exists($itemType_id, $itemTypes)) {
				return $itemTypes[$itemType_id];
			}
		}
	}

	function buildTagsArray($tags) {
		$facetTags = array();
		foreach ($tags as $tag) {
			$facetTags[$tag->id]['id'] = $tag->id;
			$facetTags[$tag->id]['name'] = $tag->name;
			$facetTags[$tag->id]['count'] = $tag->tagCount;
		}
		return $facetTags;
	}

	function buildUsersArray($users) {
		$facetUsers = array();
		foreach ($users as $user) {
			$facetUsers[$user->id]['id'] = $user->id;
			$facetUsers[$user->id]['name'] = $user->name;
			$facetUsers[$user->id]['count'] = $user->count;
		}
		return $facetUsers;
	}

	function getSelectedUser($users) {
		if (isset($_GET['user'])) {
			$user_id = $_GET['user'];
			if (array_key_exists($user_id, $users)) {
				return $users[$user_id];
			}
		}
	}

	function buildExtrasArray($extras, $extraType) {
		$facetExtras = array();
		foreach ($extras as $extra) {
			if ($extra->{$extraType}) {
				$facetExtras[1] = $extra->count;
			} else {
				$facetExtras[0] = $extra->count;
			}
		}
		return $facetExtras;
	}

	function getSelectedExtra($extras, $extraType) {
		if (isset($_GET[$extraType])) {
			$extra = $_GET[$extraType];
			if (array_key_exists($extra, $extras)) {
				return $extra;
			}
		}
	}

	function getExtraName($extraType, $value) {
		if ($value == 0) {
			return "Not " . ucfirst($extraType);
		} else {
			return ucfirst($extraType);
		}
	}

	function printHtml($html, $key, $facetsDirection, $label) {
		if ($html != '') {
			echo "<div id=\"facets-field-" . $key . "\" class=\"facets-container-" . $facetsDirection . "\">\n";
			echo "<label for=\"\">" . html_escape(__($label)) . "</label>\n";
			echo $html . "\n";
			echo "</div>\n";
		}
	}

	function getRebuiltUrl($recordType, $params) {
		return url($recordType . 's/browse', $params);
	}

	function setPreviousParameters($params) {
		if (isset($_GET['collection'])) $params['collection'] = $_GET['collection'];
		if (isset($_GET['type'])) $params['type'] = $_GET['type'];
		if (isset($_GET['tags'])) $params['tags'] = $_GET['tags'];
		if (isset($_GET['user'])) $params['user'] = $_GET['user'];
		if (isset($_GET['public'])) $params['public'] = $_GET['public'];
		if (isset($_GET['featured'])) $params['featured'] = $_GET['featured'];
		if (isset($_GET['search'])) $params['search'] = $_GET['search'];

		return $params;
	}
?>
