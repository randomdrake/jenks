<?php
/**
  * Jenk's Natural Breaks Classification Scheme with Google Charts API
  *
  * Some of this code is a translation / reworking of a script from French to English. The source script 
  * was written by Dominique Ollivier, Luc Guillemot and Dominique Pelage and can be found here: 
  * http://www.forumsig.org/showthread.php?t=22055
  *
  * Many bug fixes and significant improvements as well as the tie in to the Google Charts API were done by
  * David Drake.
  */

/** 
  * This function will determine which classNumber a particular value rank/key falls in 
  *
  * @param int $valueRank - the rank of value in the sorted list of values
  * @param int $numberOfClasses - the number of classes desired
  * @param array $numberOfElementsPerClassArray - the array of classes holding the number of elements per class
  */
function determineClass ($valueRank, $numberOfClasses, $numberOfElementsPerClassArray) {
	$minimum = 0;
	$maximum = $numberOfElementsPerClassArray[0] - 1;
	for ($ii = 0; $ii < $numberOfClasses; $ii++) {
		if ($valueRank >= $minimum && $valueRank <= $maximum) {
			$classNumber = $ii;
		}
	
		$minimum = $minimum + $numberOfElementsPerClassArray[$ii];
		$maximum = $minimum + $numberOfElementsPerClassArray[$ii + 1] - 1;
	}
	
	return $classNumber;
}

/**
  * This function will determine the maximum values for the classes
  *
  * @param array $values - the values
  * @param array $classes - the classes 
  */
function getClassMaximums ($values, $classes) {
	$numberValues = count($values);
	$numberOfClasses = count($classes);
	$maximumValues = array();

	// Make sure our values are sorted
	array_multisort($values);

	$valueIndex = 0;
	for ($classNumber = 0; $classNumber < $numberOfClasses; $classNumber++) {
		$maximumValues[$classNumber] = $values[$valueIndex];
		$valueIndex = $valueIndex + $classes[$classNumber];
	}

	$maximumValues[$numberOfClasses] = $values[$numberValues - 1];

	return $maximumValues;
}

/**
  * This function will determine the number of values that belong in each class
  *
  * @param array $values - the values
  * @param array $maximumValues - the maximum values of each class
  */
function getNumberOfValuesPerClass ($values, $maximumValues) {
	$numberOfClasses = count($maximumValues) - 1;
	$numberOfValues = count($values);
	$class = array();

	// Make sure our values are sorted
	array_multisort($values);

	$firstValueOfClass = 0;

	for ($classNumber = 1; $classNumber < $numberOfClasses; $classNumber++) {
		$crossIndex = 0;
		while ($values[$crossIndex] < $maximumValues[$classNumber] && $crossIndex < $numberOfValues) {
			$crossIndex++;
		}
		$lastClassIndex = $crossIndex - 1;

		$class[$classNumber - 1] = $lastClassIndex - $firstValueOfClass + 1;
		$firstValueOfClass = $lastClassIndex + 1;
	}

	$class[$classNumber - 1] = $numberOfValues - $firstValueOfClass;

	return $class;
}	

/**
  * Calculates an average of the values in an array
  *
  * @param array $values - the array of values you want an average calculated for
  */
function calculateAverage ($values) {
	if (!is_array($values) || count($values) == 0) {
		return 0;
	}
	return array_sum($values) / count($values);
}

/**
  * The Jenks Algorithm
  *
  * This function will determine the optimal natural breaks for a set of values given a number of classes. It will return
  * an array of classes with the number of elements for each class.
  *
  * @param int $numberOfClasses - the number of classes you want
  * @param array $values - the values you are working with 
  */
function getJenksClasses ($numberOfClasses, $values) {
	// Make sure our values are sorted 
	array_multisort($values);

	// Determine the number of values
	$numberOfValues = count($values);

	// If we have the same number of unique values as classes 
	if (count(array_unique($values)) == $numberOfClasses) {
		// Then we can just count up the number of values in each of the classes and we are finished
		$classes = array_values(array_count_values($values));
		return getClassMaximums($values, $classes);
	}
	
	// We need a starting point for Jenks. Let's start with a simple quantile split
	$classSize = floor($numberOfValues / $numberOfClasses);
	for ($ii = 0; $ii < $numberOfClasses; $ii++) {
		$classes[] = $classSize;
	}
	$classes[$ii - 1] = $numberOfValues - ($numberOfClasses - 1) * $classSize;

	/**
	  * Now that we have our simple quantile split, we'll do our first round of adjustments. It's very likely the quantile split
	  * caused equal values to be split into two different classes. This will not work for our classes. We will go through each
	  * class and determine if any of the values in the classes exist in another class. If they do, we will see which class
	  * would be best for that set of values by creating feaux classes as if all of the values have been moved and determine which 
	  * move would provide the lowest sum of squared differences for that value. We will then move those values into that class.
	  */
	$valueCounts = array_count_values($values);
	$changeOccurred = true;
	$firstIterationCounter = 0;
	do {
		$changeOccurred = false;
		if ($numberOfClasses == 6) {
			$numsixcounter++;
			if ($numsixcounter == 20) {
				exit;
			}
		}
		$startingValuesKey = 0;
		$currentClassesArray = array();
		foreach ($classes as $numberValuesInClass) {
			$currentClassesArray[] = array_slice($values, $startingValuesKey, $numberValuesInClass);
			$startingValuesKey += $numberValuesInClass;
		}
		foreach ($currentClassesArray as $key => $class) {
			if ($key == $numberOfClasses - 1) {
				break;
			}
			/**
			  * Go through each class. If the class only has one number, we are good. Let's move the rest of the numbers 
			  * into this class if they exist elsewhere
			  */
			if (count(array_unique($class)) == 1) {
				$onlyValueInClass = $class[0];
				if ($classes[$key] != $valueCounts[$onlyValueInClass]) {
					// Then there are other classes with this value. We've got some moving to do.
					$numberOfValuesToMove = $valueCounts[$onlyValueInClass] - $classes[$key];
					$classKeyToTry = $key + 1;
					while ($numberOfValuesToMove > 0) {
						// We have to start subtracting from classes that are suitable
						if ($classes[$classKeyToTry] <= 1) {
							do {
								$classKeyToTry++;
								if ($classKeyToTry > $numberOfClasses - 1) {
									$classKeyToTry = 0;
								}
							} while ($classKeyToTry == $key);
						}
						$classes[$classKeyToTry]--;
						$classes[$key]++;
						$numberOfValuesToMove--;					
					}
					$changeOccurred = true;
					break;
				}
			} else {
				// There are more than one value in this class
				$valuesInThisClass = array_count_values($class);
				$value = max($class);
				$numberTimesValueInClass = $valuesInThisClass[$value];
				if ($numberTimesValueInClass != $valueCounts[$value]) {
					/**
					  * Then there are more of these in a different class. If the next class is nothing but this value, we 
					  * know we will just move the values to that class.
					  */
					if (count(array_unique($currentClassesArray[$key + 1])) == 1) {
						// The next class up is made up entirely of this class. We will move the numbers to it.
						$numberValuesToMove = $numberTimesValueInClass;
						for ($ii = 0; $ii < $numberValuesToMove; $ii++) {
							$classes[$key]--;
							$classes[$key + 1]++;
						}
						if ($classes[$key + 1] != $valueCounts[$value]) {
							// Then we may have moved all the values out of this class into the next, but there are still more
							$numberLeftToMove = $valueCounts[$value] - $classes[$key + 1];
							$classKeyToTry = $key + 2;
							while ($numberLeftToMove > 0) {
								// We have to start subtracting from classes that are suitable
								if ($classes[$classKeyToTry] <= 1) {
									$classKeyToTry++;
								}
								$classes[$classKeyToTry]--;
								$classes[$key + 1]++;
								$numberLeftToMove--;					
							}
						}	
					} else {
						/**
						  * There are other numbers in the next class besides the one we are working with. Let's create
						  * the two classes as if we had put all of the values in the class. Then, we'll find out which 
						  * provides the best SSD for our current $value. 
						  */
						$firstComparisonClass = $currentClassesArray[$key];
						$secondComparisonClass = $currentClassesArray[$key + 1];
						$numberValuesForClass = array_count_values($firstComparisonClass);
						$numberValuesToAddFirstClass = $valueCounts[$value] - $numberValuesForClass[$value];
						for ($ii = 0; $ii < $numberValuesToAddFirstClass; $ii++) {
							$firstComparisonClass[] = $value;
						}
						$firstClassAverage = calculateAverage($firstComparisonClass);
						$numberValuesForClass = array_count_values($secondComparisonClass);
						$numberValuesToAddSecondClass = $valueCounts[$value] - $numberValuesForClass[$value];
						for ($ii = 0; $ii < $numberValuesToAddSecondClass; $ii++) {
							$secondComparisonClass[] = $value;
						}
						$secondClassAverage = calculateAverage($secondComparisonClass);

						// Now that we have built our two feaux classes, let's see which provides us with the best SSD for the value
						$firstSSD = pow($value - $firstClassAverage, 2);
						$secondSSD = pow($value - $secondClassAverage, 2);
						if ($firstSSD < $secondSSD) {
							$classKeyToAddTo = $key;
							$classKeyToSubtractFrom = $key + 1;
							$numberValuesToMove = $numberValuesToAddFirstClass;
						} else {
							$classKeyToAddTo = $key + 1;
							$classKeyToSubtractFrom = $key;
							$numberValuesToMove = $numberValuesToAddSecondClass;
						}
						for ($ii = 0; $ii < $numberValuesToMove; $ii++) {
							$classes[$classKeyToAddTo]++;
							$classes[$classKeyToSubtractFrom]--;
						}
					}
					$changeOccurred = true;
					break;
				}
			}
		}
	} while ($changeOccurred == true);

	/**
	  * Now that we are sure our classes have been split in a way that values are not spread amongst different classes, 
	  * we can do work optimizing our classes further. We will look at the highest and lowest values for each class and 
	  * determine if those sets of values would be better suited in the class above, below, or if they are good where
	  * they are at. We do not need to look at values in the middle of each class because moving a value in the middle
	  * to a class above or below it would put our classes out of numeric order and does not make sense. We also do not
	  * look at the largest or smallest values because those should stay in their lowest and highest class respsectively.
	  */
	$changeOccurred = 1;
	$iterationCounter = 0;
	$distinctValues = array_keys($valueCounts);
	$numberDistinctValues = count($distinctValues);
	$currentClassesArray = array();
	$startingValuesKey = 0;
	foreach ($classes as $numberValuesInClass) {
		$currentClassesArray[] = array_slice($values, $startingValuesKey, $numberValuesInClass);
		$startingValuesKey += $numberValuesInClass;
	}
	
	do {
		$changeOccurred = 0;

		// We only want to work on the minimums and maximums of the classes
		$minimumValue = min($values);
		$maximumValue = max($values);
		$valuesToWorkWith = array();
		foreach ($currentClassesArray as $class) {
			// We don't want to check values that are in classes by themselves because we cannot improve them
			if (count(array_unique($class)) != 1) {
				$minValueForArray = min($class);
				if ($minValueForArray != $minimumValue && $minValueForArray != $maximumValue) {
					$valuesToWorkWith[] = $minValueForArray;
				}
				$maxValueForArray = max($class);
				if ($maxValueForArray != $minimumValue && $maxValueForArray != $maximumValue) {
					$valuesToWorkWith[] = $maxValueForArray;
				}
			}
		}

		$numberOfValuesToWorkWith = count($valuesToWorkWith);
		if ($numberOfValuesToWorkWith > 0) {
			for ($ii = 0; $ii < $numberOfValuesToWorkWith; $ii++) {
				$iterationCounter++;
				$thisValue = $valuesToWorkWith[$ii];
				
				/**
				  * We will need to create feaux classes above and below the current class to see if the squared deviation decreases.
				  * If it does, we'll move the values into that class.
				  */
				$currentClassKeyForValue = determineClass(array_search($thisValue, $values), $numberOfClasses, $classes);
				
				$firstComparisonClassKey = $currentClassKeyForValue - 1;
				$secondComparisonClassKey = $currentClassKeyForValue + 1;
				$squaredDeviationsArray = array();
				if ($firstComparisonClassKey > 0) {
					$firstComparisonClass = $currentClassesArray[$firstComparisonClassKey];
					$numberValuesForClass = array_count_values($firstComparisonClass);
					$numberValuesToAddFirstClass = $valueCounts[$thisValue] - $numberValuesForClass[$thisValue];
					for ($jj = 0; $jj < $numberValuesToAddFirstClass; $jj++) {
						$firstComparisonClass[] = $thisValue;
					}
					$firstClassAverage = calculateAverage($firstComparisonClass);
					$squaredDeviationsArray[$firstComparisonClassKey] = pow($thisValue - $firstClassAverage, 2);
				}
				if ($secondComparisonClassKey < $numberOfClasses) {
					$secondComparisonClass = $currentClassesArray[$secondComparisonClassKey];
					$numberValuesForClass = array_count_values($secondComparisonClass);
					$numberValuesToAddSecondClass = $valueCounts[$thisValue] - $numberValuesForClass[$thisValue];
					for ($jj = 0; $jj < $numberValuesToAddSecondClass; $jj++) {
						$secondComparisonClass[] = $thisValue;
					}
					$secondClassAverage = calculateAverage($secondComparisonClass);
					$squaredDeviationsArray[$secondComparisonClassKey] = pow($thisValue - $secondClassAverage, 2);
				}

				$currentClassAverage = calculateAverage($currentClassesArray[$currentClassKeyForValue]);
				$currentSquaredDeviation = $squaredDeviationsArray[$currentClassKeyForValue] = pow($thisValue - $currentClassAverage, 2);

				$minSquaredDeviation = min($squaredDeviationsArray);
				if ($minSquaredDeviation != $currentSquaredDeviation) {
					$minSquaredDeviationKey = array_search($minSquaredDeviation, $squaredDeviationsArray);

					// Then we need to move these values from the class they are in to the class they should be in
					$numberOfThisValue = $valueCounts[$thisValue];
					$classes[$minSquaredDeviationKey] += $numberOfThisValue;
					$classes[$currentClassKeyForValue] -= $numberOfThisValue;
					$changeOccurred = 1;

					// Regenerate our classes as they currently are
					$startingValuesKey = 0;
					$currentClassesArray = array();
					foreach ($classes as $numberValuesInClass) {
						$currentClassesArray[] = array_slice($values, $startingValuesKey, $numberValuesInClass);
						$startingValuesKey += $numberValuesInClass;
					}
				}
			}
		}
	} while ($changeOccurred == 1);

	return getClassMaximums($values, $classes);
}

/**
  * This function determines the optimum number of classes for a given set of values by calculating the goodness of variance fit (GVF)
  * for class sizes 4 - 7. It will return the maximum values for each class and the number of values for each class in an array for the
  * number of classes that best fits the data.
  *
  * @param array $values - the values you want to determine the optimum deviation for
  * @param int $minimumNumberOfClasses - the minimum number of classes you want to try
  * @param int $maximumNumberOfClasses - the maximum number of classes you want to try
  * @return array - the maximum values for each array and the number of values for each class
  */
function getOptimalClassInformation ($values, $minimumNumberOfClasses = 4, $maximumNumberOfClasses = 7) {
	// Make sure our values are sorted
	array_multisort($values);

	$numberOfUniqueValues = count(array_unique($values));
	if ($numberOfUniqueValues <= $maximumNumberOfClasses) {
		$maximumValues = getJenksClasses ($numberOfUniqueValues, $values);
		$numberOfValuesPerClass = getNumberOfValuesPerClass ($values, $maximumValues);
		return array ($maximumValues, $numberOfValuesPerClass);
	} else {
		$valuesAverage = calculateAverage($values);
		$SDAM = 0;
		foreach ($values as $value) {
			$SDAM += pow ($value - $valuesAverage, 2);
		}

		for ($classNumber = $minimumNumberOfClasses; $classNumber <= $maximumNumberOfClasses; $classNumber++) {
			$maximumValues = getJenksClasses ($classNumber, $values);
			$numberOfValuesPerClass = getNumberOfValuesPerClass ($values, $maximumValues);
			$arraySliceIndex = 0;
			$SDCM = 0;
			foreach ($numberOfValuesPerClass as $numberOfValues) {
				$thisClass = array_slice($values, $arraySliceIndex, $numberOfValues);
				$thisClassAverage = calculateAverage($thisClass);
				$deviationsSumForClass = 0;
				foreach ($thisClass as $thisValue) {
					$deviationsSumForClass += pow ($thisValue - $thisClassAverage, 2);
				}
				$SDCM += $deviationsSumForClass;
				$arraySliceIndex += $numberOfValues;
			}
			$gvfsArray[$classNumber] = 1 - ($SDCM / $SDAM);
			$returnArraysToClassNumbers[$classNumber] = array ($maximumValues, $numberOfValuesPerClass);
		}
	}

	return $returnArraysToClassNumbers[array_search(max($gvfsArray), $gvfsArray)];
}

/**
  * Takes an array of values and an array of numbers per class from Jenks to provide us with a nice list of what the ranges are
  *
  * @param array $values - the original array of values
  * @param array $jenksArray - the array of how many values should go in each class from Jenks
  */
function getMapKeys ($values, $jenksArray) {
	// Make sure our values are sorted
	array_multisort($values);

	if (count($jenksArray) == 2) {
		// If the Jenks array just contains a single value and the max, then we can just return that value
		return array (0 => $jenksArray[1]);
	}
	$returnArray = array();
	$startingValue = $jenksArray[0];
	$arrayCount = count($jenksArray);
	$rangeString = 1;
	$stoppingValue = $jenksArray[$arrayCount - 1];
	for ($ii = 1; $ii < $arrayCount; $ii++) {
		$maxValue = $jenksArray[$ii];
		if ($maxValue == $stoppingValue) {
			if ($rangeString == $maxValue) {
				$rangeString = $maxValue;
		 	} else {
				foreach ($values as $value) {
					if ($value < $maxValue) {
						$max = $value; 
					} else if ($rangeString == $max) {
						break;
					} else {
						$rangeString .= ' - ' . $max;
						break;
					}
				}
			}
		} else {
			foreach ($values as $value) {
				if ($value < $maxValue) {
					$max = $value; 
				} else if ($max == $rangeString) {
					break;
				} else {
					$rangeString .= ' - ' . $max;
					break;
				}
			}
		}

		$returnArray[] = $rangeString;
		$rangeString = $jenksArray[$ii];
	}

	return $returnArray;
}

/** 
  * This function will display a map using the Google Charts API. Information about the API can be found
  * here: http://code.google.com/apis/chart/types.html#maps
  *
  * We are using Jenks Natural Breaks Classification Scheme to create our breaks in our data and our keys
  * for our maps.
  *
  * The output for this will be a URI that looks like this: http://chart.apis.google.com/chart?cht=t
  *	&chs=440x220&chco=DCDCDC,FDFAD1,FFE9B0,FFBB7D,E5280C,A61900&chtm=usa&chd=t:20,20,20,20,20,20,20,20,
  * 20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,40,40,40,40,40,40,40,40,40,40,40,40,60,60,60,60,60,60,
  * 60,60,60,80,80,80,80,100,100&chld=VTHIDESDWVDCMERIWYARNENHAKNMMSLAOKIDIANVALUTSCCTKYTNINMDWIMNMOOR
  * MANCAZVAGAMIOHCOPANJILWAFLTXNYKSCAMT&chf=bg,s,EAF7FE
  *
  * Due to the limits of the numbers and formats of numbers that can be sent to the charting API, we break 
  * our classes up into even numbers that have the most distance between them as possible but still remain 
  * within 100. The rest of the arguments are fully explained in the API documentaiton.
  *
  * @param array $values - the values of the map you are trying to create
  * @param array $locations - either an array of 2-digit state codes or an array of ISO 3166-1-alpha-2 country 
  *						      codes. This array should correspond to the $values in the way it is sorted.
  * @param string $map_type - the type of map you would like. Should be from this list:
  *							  'africa', 'asia', 'europe', 'middle_east', 'south_america', 'usa', 'world'
  * @param boolean $return_data - if return data is set to true, this function will return the URI and the
  *								  colors and data needed to display the map key
  * @return mixed - either nothing or an array containing the return data
  */
function displayGoogleMap($values, $locations, $map_type, $return_data = false) {
	// Color of locations with no values
	$empty_color = 'DCDCDC';

	// Color of the water behind the maps
	$water_color = 'FAFAFA';

	// Make sure our values are sorted
	array_multisort($values);

	// We will grab the maximum values from each class as determined by the Jenks algorithm
	$class_information = getOptimalClassInformation($values);
	$jenks_max_values_per_class = $class_information[0];
	$number_values_per_class = $class_information[1];
	$number_of_classes = count($number_values_per_class);
  	$valid_map_types = array('africa', 'asia', 'europe', 'middle_east', 'south_america', 'usa', 'world');
	$display_map_types = array ('Africa', 'Asia', 'Europe', 'Middle East', 'South America', 'USA', 'World');

	// With this information, we will get our map keys
	$breaks_array = getMapKeys($values, $jenks_max_values_per_class);

	if ($number_of_classes <= 5) {
		$map_colors = array ($empty_color, 'FDFAD1', 'FFE9B0', 'FFBB7D', 'E5280C', 'A61900');
		$google_colors = array ($empty_color, 'FEECB6', 'FECC91', 'F4804F', 'D82509', 'A51900');

		// Google renders the colors for 2 classes in a particular way
		if ($number_of_classes == 2) {
			$google_colors = array ($empty_color, 'FEECB6', 'FECD91');
		}

		$class_multiplier = 20;
	} else if ($number_of_classes > 5) {
		$map_colors = array ($empty_color, 'FFFFFF', 'FDFAD1', 'FFE9B0', 'FFBB7D', 'FF7A3D', 'E5280C', 'A61900');
		$google_colors = array ($empty_color, 'FDFAD1', 'FDEDB9', 'FED095', 'FEA265', 'F96933', 'E2270B', 'AD1A01');

		// Google renders the colors for 6 classes in a particular way
		if ($number_of_classes == 6) {
			$google_colors = array ($empty_color, 'FDECB8', 'FCD095', 'FDA165', 'F86732', 'E2280B', 'AD1A01');
		}
		$map_colors = $google_colors;
		$class_multiplier = round(100 / $number_of_classes, 2);
	}

	// Let's build our URI
	$uri = 'http://chart.apis.google.com/chart?cht=t&chs=440x220&chco=';
	foreach ($map_colors as $color) {
		$uri .= $color . ',';
	}
	$uri = substr($uri, 0, -1);
	$uri .= '&chtm=' . $map_type;

	// Here we will build the location abbreviations and location values to go in the URI
	$max_value = $values[0];
	$key = 0;
	foreach ($locations as $location_abbrev => $number_from_location) {
		$location_codes .= $location_abbrev;
		$class_number = round((determineClass($key, $number_of_classes, $number_values_per_class) + 1) * $class_multiplier);
		$location_values .= $class_number . ',';
		$key++;
	}
	$location_values = substr($location_values, 0, -1);

	// Finish off our URI by tacking on the values, codes, and color for the water
	$uri .= '&chd=t:' . $location_values .
			'&chld=' . $location_codes .
			'&chf=bg,s,' . $water_color;

	// We need to check and see if they just wanted data returned or if they wanted the map displayed
	if ($return_data) {
		// Let's build our array for the map colors and data and return our data
		$map_key_array[$empty_color] = 'No Data';
		for ($ii = 1; $ii <= $number_of_classes; $ii++) {
			$color = $google_colors[$ii];
			$text = $breaks_array[$ii - 1];
			$map_key_array[$color] = $text;
		}			
		return array ($uri, $map_key_array);
	} else {
		// Let's display it
		$empty_color = $google_colors[0];
?>
				<div class="map_legend">
					<table>
						<tr>
							<td class="mapcolor" bgcolor="#<?php echo $map_colors[0]; ?>">
								&nbsp;&nbsp;&nbsp;
							</td>
							<td>
								No Data
							</td>
						</tr>
<?php
		for ($ii = 1; $ii <= $number_of_classes; $ii++) {
			$color = $google_colors[$ii];
			$text = $breaks_array[$ii - 1];
			echo '<tr>' . "\n" .
				 '<td class="mapcolor" bgcolor="#' . $color . '" border="1">' . "\n" . 
				 '&nbsp;&nbsp;&nbsp;' . "\n" .
				 '</td>' . "\n" . 
				 '<td>' . "\n" .
				 $text . "\n" .
				 '</td>' . "\n" .
				 '</tr>';
		}
?>
					</table>
				</div>
<?php echo '<img ' . $margin . 'src="' . $uri . '" />'; ?>
			</div>
		</div>
	</div>
</div>
<?php
	} # end case they didn't want the data returned
} # end function displayGoogleMap

?>
