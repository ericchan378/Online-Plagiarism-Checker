<?php
ini_set('max_execution_time', 300);

//Create a button that takes the user back to the text input screen.
echo '<button onclick = "window.location.href = \'370FinalProject.html\';"> Check More Text </button><br>';

//Get input from user submission.
$text = $_POST["text"];
//Split the text by white space and store it into an array.
$inputText = preg_split('/[\s]+/', $text); 
$sizeOfText = count($inputText);

//Remove stop words to optimize the text for the queries.
$optimizedText = removeStopWords($text);
//Split the optimized text by white space into an array.
$queryArray = preg_split('/[\s]+/', $optimizedText);
$sizeOfQueryArray = count($queryArray);

if($sizeOfQueryArray < 6){
	echo "No results to show. Press the Check More Text button to go back.";
}
else{
	//Array containing URLs from Google API whose HTML is to be compared with input text.
	$urls = array();
	//Array of contiguous word matches. Index of $matches associated with index of $urls.
	$matches = array();
	//Total number of word matches also associated with its URL. 
	$totalMatches = array();

	//Create a list of queries by splitting them into groups of 32 words and store them into the array $queries.
	$queries = array();
	$queryIndex = 0;
	$a = 0;
	while($a < $sizeOfQueryArray){
		$b = 0;
		$query = array();
		while(($b < 32) && ($a < $sizeOfQueryArray)){
			$query[$b] = $queryArray[$a];
			$a++;
			$b++;
		}
		$queries[$queryIndex] = implode(" ", $query);
		$queryIndex++;
	}

	$sizeOfQueries = count($queries);
	//Call Google API on each query
	for($i = 0; $i < $sizeOfQueries; $i++){
		callGoogleAPI($queries[$i]);
	}

	$sizeOfURLS = count($urls);
	//Compare and find matches of each URL
	for($i = 0; $i < $sizeOfURLS; $i++){
		compareText($urls[$i], $i);	
	}

	//Output URL, its percent match, and its list of matches.
	output();
}

//This function takes a string and removes all the stop words in the array from it.
//It will return a string without those words.
function removeStopWords($inputString){
	$stopWords = array("the","and","a","that","I","it","not","he","as","you","this","but","his","they","her","she","or","an","will","my","one","all","would","there","their","to","of","in","for","on","with","at","by","from","up","into","over","after","be","have","do","say","get","make","go","know","take","see","come","think","look","want","give","use","find","tell","ask","work","seem","feel","try","leave","call");

	return preg_replace('/\b('.implode('|', $stopWords) . ')\b/', '', $inputString);
}

//This function takes a string, encodes it to URL format and queries the API.
//The JSON response is converted to a PHP object and the URLs are extracted from it,
//adding the URL to the array $urls if it doesn't already exist. 
function callGoogleAPI($queryString){
	global $urls;

	//Encode the query for it to be passed into the API.
	$q = urlencode($queryString);
	$google = "https://www.googleapis.com/customsearch/v1?key=AIzaSyDrRpAfprjN8C4M16Z_cGXwMLrDGMuFMiA&cx=014175807902713496140:lutx1h8_kos&q=" . $q;

	//Get the API response as a JSON object.
	$body = file_get_contents($google);
	//Convert the JSON object into a PHP object.
	$json = json_decode($body);

	//Extract the URLs from the API response and store it into the 
	//array $urls if it doesn't already exist in the array
	for($i = 0; $i < count($json->items); $i++){
		$link = $json->items[$i]->link;
		if(in_array($link, $urls) == FALSE){
			array_push($urls, $link);
		}
	}
}

function compareText($url, $urlIndex){
	global $inputText;
	global $sizeOfText;
	global $matches;
	global $totalMatches;

	//Get the HTML contents for this website.
	$html = file_get_contents($url);
	//Remove HTML tags to get plain text data.
	$data = strip_tags($html);
	//Split the text data by white space into an array.
	$webText = preg_split('/[\s]+/', $data);
	$sizeOfWebText = count($webText);

	$seq = array();
	$seqIndex = 0;

	$totalMatches[$urlIndex] = 0;

	//Iterate through both texts to find contiguous matching strings and store them into an array
	//at the same index the website URL is stored in its array.
	for ($i = 0; $i < $sizeOfWebText; $i++){  
		$s = array();
		$contiguousCount = 0;

		for ($j = 0; $j < $sizeOfText; $j++){
			
			//If word is the same then add to $s array
			if ($webText[$i] == $inputText[$j]){

				$s[$contiguousCount] = $inputText[$j];

				$contiguousCount++;

				//Go to next word in the webText
				$i++;

				//Exit inside loop if next word of both texts are not the same
				//so to only get contiguous matches
				if(($i < $sizeOfWebText) && (($j + 1) < $sizeOfText)){
					if($webText[$i] != $inputText[$j + 1]){
						$i--; //Decrement outer loop if breaking from inner loop or will skip one in outer
						break;
					}
				}
			}
		}

		//Add to total matches for this particular URL if the string contains as least 6 contiguous words.
		//Store the string into an array, $seq.
		if($contiguousCount >= 6){
			$totalMatches[$urlIndex] += $contiguousCount;
			$seq[$seqIndex] = $s;
			$seqIndex++;
		}
	}
	//Add contiguous matching words to $matches array associated with the url
	$matches[$urlIndex] = $seq;
}

//Outputs the URLs, matching strings, and percent match for each website if there are any matching strings for that website,
//using the data stored in $urls, $matches, and $totalMatches arrays. 
function output(){
	global $sizeOfText;
	global $urls;
	global $matches;
	global $totalMatches;
	$length = count($urls);
	$totalMatchCount = 0;

	//Iterate through the count of total matches for each website.
	//If there are no matches for all the websites, 
	//then report to the user that there are no results to show.
	for($a = 0; $a < $length; $a++){
		$totalMatchCount += $totalMatches[$a];
	}
	if($totalMatchCount == 0){
		echo "No results to show. Press the Check More Text button to go back.";
	}
	else{
		//Iterate through the three arrays which store the results and output the websites,
		//the percent match, and the list of matching strings found on that website if the
		//website contains at least one matching string.
		for($i = 0; $i < $length; $i++){
			if($totalMatches[$i] > 0){
				//Calculate the percent match by dividing the total number of matched words
				//by how many words are in the input text and multiplying by 100, to 2 decimal places.
				$percent = round(($totalMatches[$i] / $sizeOfText) * 100, 2);
				echo "<h3>" . $urls[$i] . "</h3>";
				echo "<h4> Percent Match: </h4>" . $percent . "%.<br>";
				echo "<h4> Matches: </h4>";
				$jlength = count($matches[$i]);

				//Iterate through the 3 dimensional array containing the strings found to match 
				//strings of the input text.
				for($j = 0; $j < $jlength; $j++){
					$klength = count($matches[$i][$j]);
					echo "-";
					for($k = 0; $k < $klength; $k++){
						echo $matches[$i][$j][$k] . " ";

					}
					echo "<br>";
				}
			}
		}
	}
}

?>
