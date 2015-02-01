<?php
/*******************************************************************************
*	This file contains the SOAGraph Printer for SemanticResultFormats
*   (https://www.mediawiki.org/wiki/Extension:Semantic_Result_Formats)
*
*	Copyright (c) 2015 Jan de Baat - De B.A.A.T.
*
*   SOAGraph Printer is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   SOAGraph Printer is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with SOAGraph Printer. If not, see <http://www.gnu.org/licenses/>.
*******************************************************************************/

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

/**
 * This is a contribution to Semtantic Result Formats (SRF) which are an
 * extension of Semantic MediaWiki (SMW) which in turn is an extension
 * of MediaWiki.
 *
 * SRF defines certain "printers" to render the results of SMW semantic
 * "ASK"-queries. Some of these printers make use of the GraphViz/dot
 * library (which is wrapped by a separate MediaWiki extension).
 *
 * The purpose of this extension, is to render results of ASK-Queries
 * (e.g. Classes with Attributes) as GraphViz-layouted process graphs
 * in a Service Oriented Architecture (SOA) context.
 *
 * In order to use this printer you need to have both
 * the GraphViz library installed on your system and
 * have the GraphViz MediaWiki extension installed.
 * 
 * This code is based on the code in SRF_Boilerplate.php and SRF_Graph.php
 * 
 * @file SRF_SOAGraph.php
 * @author DeBAAT < SOAGraph@de-baat.nl >
 * @ingroup SemanticResultFormats
 *
 * @since 1.8
 *
 */

// global variable defining picture path

$srfgPicturePath = "formats/graphviz/images/";

class SRFSOAGraph extends SMWResultPrinter {		// copied from SRFBoilerplate
	
	public static $NODE_SHAPES = array(
		'box',
		'box3d',
		'circle',
		'component',
		'diamond',
		'doublecircle',
		'doubleoctagon',
		'egg',
		'ellipse',
		'folder',
		'hexagon',
		'house',
		'invhouse',
		'invtrapezium',
		'invtriangle',
		'Mcircle',
		'Mdiamond',
		'Msquare',
		'none',
		'note',
		'octagon',
		'parallelogram',
		'pentagon ',
		'plaintext',
		'point',
		'polygon',
		'rect',
		'rectangle',
		'septagon',
		'square',
		'tab',
		'trapezium',
		'triangle',
		'tripleoctagon',
	);

	public static $mGraphVizImageRenderGraphStart = 'strict digraph %s { size ="%s;" ';
	public static $mGraphVizImageRenderGraphEnd = ' }';
	public static $mGraphVizImageRenderEdge = ' -> ';
	public static $mGraphVizImageRenderEdgeEnd = '; ';
	public static $mGraphVizImageRenderURL = 'URL="%s", target="_parent",';
	
	public static $mSOAVisNodeFormatDefaultLast = 'style=filled';

	public static $mSOAVisNodeFormatFillColorStart = 'fillcolor=orange,';
	public static $mSOAVisNodeFormatFillColorDown = 'fillcolor=yellow,';
	public static $mSOAVisNodeFormatFillColorUp = 'fillcolor=springgreen,';
	public static $mSOAVisNodeFormatFillColorDefault = 'fillcolor=grey,fontcolor=white,';

	public static $mSOAVisNodeFormatShapeService = 'shape=box,';
	public static $mSOAVisNodeFormatShapeSystemDown = 'shape=invtriangle,';
	public static $mSOAVisNodeFormatShapeSystemUp = 'shape=triangle,';

	public static $mSOAVisNodeFormatStart = ' [';
	public static $mSOAVisNodeFormatEnd = ']; ';
	
	protected $mDefaultFillColor = "#ffffff";
	protected $mDefaultFontColor = "#ffffff";
	protected $mDefaultColor     = "#000000";
	protected $mDefaultShape     = "shape=box";

	protected $mSOAVisNodeDirectionStart = 'Start';
	protected $mSOAVisNodeDirectionDown = 'Down';
	protected $mSOAVisNodeDirectionUp = 'Up';
	

	// To be used as index in the SOAVisNode
	protected $mSOAVisNodeNodeID = 'nodeID';
	protected $mSOAVisNodeNodeName = 'nodeName';
	protected $mSOAVisNodeChildID = 'nodeChildID';
	protected $mSOAVisNodeChildName = 'nodeChildName';
	protected $mSOAVisNodeColor = 'nodeColor';
	protected $mSOAVisNodeColorColumn = 'nodeColorColumn';
	protected $mSOAVisNodeDirection = 'nodeDirection';
	protected $mSOAVisNodeEdgeID = 'edgeID';
	protected $mSOAVisNodeFillColor = 'nodeFillColor';
	protected $mSOAVisNodeFillColorColumn = 'nodeFillColorColumn';
	protected $mSOAVisNodeFontColor = 'nodeFontColor';
	protected $mSOAVisNodeFontColorColumn = 'nodeFontColorColumn';
	protected $mSOAVisNodeLabel = 'nodeLabel';
	protected $mSOAVisNodeShape = 'nodeShape';
	protected $mSOAVisNodeShapeColumn = 'nodeShapeColumn';
	protected $mSOAVisNodeToDraw = 'nodeToDraw';
	protected $mSOAVisNodeType = 'nodeType';
	protected $mSOAVisNodeTypeSystem = 'system';
	protected $mSOAVisNodeTypeService = 'service';
	protected $mSOAVisNodeUrl = 'nodeUrl';
	protected $mSOAVisNodeValue = 'nodeValue';

	protected $mStartFillColor;
	protected $mStartFontColor;
	protected $mStartColor;

	/**
	 * Array of SOAVisNodes objects, indexed by their ID
	 * 
	 * The SOAVisNode object is an array with the following keys:
	 * 'nodeID'		=> The ID of the node
	 * 'childID'	=> The ID of the child node
	 * 'typeID'		=> The type of the node, i.e. 'service', 'system', ...
	 * 
	 * 
	 * @var Array of SOAVisNodes
	 */ 
	protected $mSOAVisNodes = array();
	protected $mSOAVisEdges = array();
	
	protected $m_graphName;
	protected $m_graphSize;
	
	protected $m_rankdir;
	
	protected $m_nodeShape;
	protected $m_wordWrapLimit;
	
	protected $mMaxLevels = -1;
	protected $mMaxUpLevels = -1;
	
	protected $mStartNodes = array();
	protected $mColorMap = array();
	protected $mFillColorMap = array();
	protected $mFontColorMap = array();
	protected $mShapeMap = array();



	/**
	 * @see SMWResultPrinter::getName
	 * @return string
	 */
	public function getName() {
		// Add your result printer name here
		return wfMessage( 'srf-printername-soagraph' )->text();		// copied from -Boilerplate
	}

	/**
	 * Recursively create a string of a dataRow
	 * 
	 * @return string
	 */
	public function printData( $level, $dataName, $dataRow ) {
		$printDataOutput = '. ';
		for ($i=0;$i<$level;$i++) {
			$printDataOutput .= '. ';
		}
		$printDataOutput .= $level . '. ';
		$printDataOutput .= ' data[' . $dataName . '] => ' . $dataRow . '.<br/>';
		foreach ( $dataRow as $dataRow_Name => $dataRow_Row ) {
			$newLevel = $level + 1;
			$printDataOutput .= $this->printData ( $newLevel, $dataRow_Name, $dataRow_Row );
		}
		return $printDataOutput;
	}

	/**
	 * @see SMWResultPrinter::getResultText
	 *
	 * @param SMWQueryResult $result
	 * @param $outputMode
	 *
	 * @return string
	 */
	protected function getResultText( SMWQueryResult $result, $outputMode ) {

		// Check whether the Graphviz package is installed
		if ( !is_callable( 'renderGraphviz' ) ) {
			wfWarn( 'The SRF SOAGraph printer needs the GraphViz extension to be installed.' );
			return '';
		}

		global $wgGraphVizSettings;

		$graphInput = "Initial graphInput";
		$this->isHTML = true;
		
		$this->processParameters ($this->params);
		$output = '';

		// Data processing
		// It is advisable to separate data processing from output logic
		$data = $this->getResultData( $result, $outputMode );

		// Generate the text to generate the graph from
		$graphInput = $this->getGraphvizTreeText($this->mStartNodes);

		// Calls renderGraphViz function from MediaWiki GraphViz extension
		$output .= renderGraphviz( $graphInput );

		if ($this->getParamValue ('debug')) {
			$debugOutput  = '';
			$debugOutput .= '<br/> Graph Input for renderGraphviz:<br/>';
			$debugOutput .= '<pre>' . $graphInput . '</pre>';
			$debugOutput .= '<br/>';
			$output .= $debugOutput;
		}

		return $output;

	}

	/**
	 * Returns an array with data
	 *
	 * @since 1.8
	 *
	 * @param SMWQueryResult $result
	 * @param $outputMode
	 *
	 * @return array
	 */
	protected function getResultData( SMWQueryResult $result, $outputMode ) {

		$data = array();

		$headers = array();
		$this->mSOAVisNodeChildID = '';
		foreach ( $result->getPrintRequests() as /* SMWPrintRequest */ $pr ) {
			$attribs = array();
			$columnClass = str_replace( array( ' ', '_' ), '-', strip_tags( $pr->getText( SMW_OUTPUT_WIKI ) ) );
			//$columnLabel = $pr->getText();
			$columnLabel = $this->normaliseValue( $pr->getText( SMW_OUTPUT_WIKI )); //$columnClass;
			$attribs[$this->mSOAVisNodeLabel] = $columnLabel;
			$attribs['label'] = $columnClass;
			$attribs['linker'] = $pr->getText( $outputMode, ( $this->mShowHeaders == SMW_HEADERS_PLAIN ? null : $this->mLinker ) );
				
			// Get the label for the ChildID as value of ColumnTo
			if ($columnLabel == $this->m_columnTo) {
				$this->mSOAVisNodeChildID = $columnLabel;
			}
				
			$headers[] = $attribs;
		}
		if ($this->mSOAVisNodeChildID == '') {
			$this->mSOAVisNodeChildID = $headers['1'][$this->mSOAVisNodeLabel];
		}
		

		/**
		 * Get all values for all rows that belong to the result set
		 * @var SMWResultArray $rows
		 */
		$rowNumber = 0;
		while ( $rows = $result->getNext() ) {

			$rowNumber++;
			$cellnumber = 0;
			$newNode = array();
			$newEdge = array();
			$nodeHeader = 'START NODE HEADER';
			$nodeFullUrl = 'START NODE FULLURL';
			$nodeLocalUrl = 'LOCAL URL';
			foreach ( $rows as $i => $field ) {
				$cellnumber++;
				$dataValues = array();
		
				while ( ( $dv = $field->getNextDataValue() ) !== false ) {
					$dataValues[] = $dv;
				}

				$columnValues = array();
				$columnValueNumber = 0;
				$columnLabel = $headers[$i][$this->mSOAVisNodeLabel];
				if ( count( $dataValues ) > 0 ) {
					$childNodes = array();
					foreach ( $dataValues as $dv2 ) {
						$nodeName = str_replace( array( ' ', '_' ), '-', strip_tags( $dv2->getText( SMW_OUTPUT_WIKI ) ) );
						$nodeID = $this->normaliseValue($dv2->getText( SMW_OUTPUT_WIKI ));
						$nodeName = '"' . $nodeName . '"';
						$columnValueFullUrl = str_replace('&', '&amp;', $dv2->getTitle()->getFullURL());
						if ($columnLabel == '') {
							// Process item
							$nodeHeaderID = $nodeID;
							$nodeHeaderName = $nodeName;
							$nodeFullUrl = $columnValueFullUrl;
							$nodeLocalUrl = $dv2->getTitle()->getLocalURL();
						} else {
							// Process values from this column
							$columnValues[$this->mSOAVisNodeNodeID] = $nodeID;
							$columnValues[$this->mSOAVisNodeNodeName] = $nodeName;
							$columnValues[$this->mSOAVisNodeUrl] = $dv2->getTitle()->getFullURL();
							$columnValues['localUrl'] = $dv2->getTitle()->getLocalURL();
								
							// If it is a reference to a child node, create an edge with the header node
							if ($columnLabel == $this->mSOAVisNodeChildID) {
								$childNodes[] = $nodeID;
								$newEdge[$this->mSOAVisNodeEdgeID] = $nodeHeaderID . '-' . $nodeID;
								$newEdge[$this->mSOAVisNodeNodeID] = $nodeHeaderID;
								$newEdge[$this->mSOAVisNodeNodeName] = $nodeHeaderName;
								$newEdge[$this->mSOAVisNodeChildID] = $nodeID;
								$newEdge[$this->mSOAVisNodeChildName] = $nodeName;
								$newEdge[$this->mSOAVisNodeToDraw] = 0;
								$this->mSOAVisEdges[] = $newEdge;
							}

							$newNode[$columnLabel][$columnValueNumber] = $columnValues;
							$columnValueNumber++;
						}
					}
					
					// Set some parameters for the node found
					$newNode[$this->mSOAVisNodeNodeID] = $nodeHeaderID;
					$newNode[$this->mSOAVisNodeNodeName] = $nodeHeaderName;
					$newNode[$this->mSOAVisNodeDirection] = 'NO DIRECTION';
					$newNode[$this->mSOAVisNodeUrl] = $nodeFullUrl;
					if (stripos($nodeHeaderID, $this->mSOAVisNodeTypeService) === false) {
						$newNode[$this->mSOAVisNodeType] = $this->mSOAVisNodeTypeSystem;
					} else {
						$newNode[$this->mSOAVisNodeType] = $this->mSOAVisNodeTypeService;
					}
					$newNode[$this->mSOAVisNodeToDraw] = 0;
					$newNode['nodeLocalUrl'] = $nodeLocalUrl;
					$newNode[$this->mSOAVisNodeChildID . '_JdB'][] = $childNodes;
				}

			}
			
			// Add the new node found to the global list of mSOAVisNodes
			$this->mSOAVisNodes[$newNode[$this->mSOAVisNodeNodeID]] = $newNode;

		}

		// Return the data
		return $data;
	}


	/**
	 * Prepare data for the output
	 *
	 * @since 1.8
	 *
	 * @param array $data
	 * @param array $options
	 *
	 * @return string
	 */
	protected function getFormatOutput( $data, $options ) {

		$formattedOutput = 'This is the result of getFormatOutput!<br/>';

		$dataItem = array();
		foreach ( $data as $subject => $properties ) {
			foreach ( $properties as $label => $values ) {
				if (count($values['0']) > 0) {
					foreach ( $values['0'] as $Key => $Value ) {
						$formattedOutput .= '...   subject[' . $subject . '] ';
						$formattedOutput .= '. [' . $label . '] ';
						$formattedOutput .= '. [' . $Key . '] = ';
						$formattedOutput .= 'value[' . $Value . '] <br/>';
					}
				} else {
					$formattedOutput .= '...   subject[' . $subject . '] ';
					$formattedOutput .= '. [' . $label . '] has no values! <br/>';
				}
			}
		}

		return $formattedOutput;

	}



	/**
	 * Get a SOAVisNode from the list of all nodes
	 * 
	 * @return SOAVisNode
	 */
	protected function getSOAVisNode( $nodeID ) {

		foreach ( $this->mSOAVisNodes as $SOAVisNode ) {
			if (isset($SOAVisNode[$this->mSOAVisNodeNodeID])) {
				if ( $SOAVisNode[$this->mSOAVisNodeNodeID] == $nodeID) {
					return $SOAVisNode;
				}
			}
		}
		return null;

	}

	/**
	 * Generate the text to generate the Graphviz image from
	 *
	 * @since 1.8
	 *
	 * @param array $startNodeIDList
	 *
	 * @return string
	 */
	protected function getGraphvizTreeText ( $startNodeIDList ) {
		
		$strGraphvizTreeText = '';
		
		// Create a service tree for this service
		foreach ( $startNodeIDList as $startNodeID ) {
			$strGraphvizTreeNodeText = $this->GetNodeTreeText( $startNodeID );
		}


		// Create GraphViz start string
		$strGraphvizTreeText .= sprintf(self::$mGraphVizImageRenderGraphStart, $this->getParamValue('graphName'), $this->getParamValue('graphSize'));

		$strGraphvizTreeText .= $this->GetNodeTreeNodeText();
		$strGraphvizTreeText .= $this->GetNodeTreeEdgeText();
		
		// Create GraphViz terminate string
		$strGraphvizTreeText .= self::$mGraphVizImageRenderGraphEnd;

		$strGraphvizTreeText .= $strGraphvizTreeNodeText;
		return $strGraphvizTreeText;
		
	}

	/**
	 * Generate the text to generate the Graphviz image from
	 *
	 * @since 1.8
	 *
	 * @param array $startNodes
	 *
	 * @return string
	 */
	protected function GetNodeTreeText( $nodeID ) {
		$strNodeTreeText = '';
		
		// Format start node
		$strNodeTreeText .= $this->SetNodeFormatting($nodeID, $this->mSOAVisNodeDirectionStart);

		// Create the service tree in both directions up and down
		$strNodeTreeText .= $this->GetNodeTreeDown($nodeID);
		$strNodeTreeText .= $this->GetNodeTreeUp($nodeID);

		return $strNodeTreeText;
	}

	/**
	 * Generate the text to generate the Graphviz image from
	 *
	 * @since 1.8
	 *
	 * @param array $startNodes
	 *
	 * @return string
	 */
	protected function GetNodeTreeDown( $parentNodeID ) {
		$strNodeTreeDown = '';

		// Get the data for the parentNode
		$parentNode = $this->getSOAVisNode( $parentNodeID );

		// Recursively traverse all children found
		if ( isset($parentNode[$this->mSOAVisNodeChildID])) {
			
			// Process all children of this node
			$childList = $parentNode[$this->mSOAVisNodeChildID];
			if (is_array($childList)) {
				foreach($childList as $key => $childNodeRef) {

					$childNodeID = $childNodeRef[$this->mSOAVisNodeNodeID];
					$childNode = $this->getSOAVisNode( $childNodeID );
				
					if ($childNode) {
					
						// Format the node for this child
						$strNodeTreeDown .= $this->SetNodeFormatting($childNodeID, $this->mSOAVisNodeDirectionDown);
					
						// Make the edge between the parent and the child
						$strNodeTreeDown .= $this->MakeNodeTreeEdge($parentNodeID, $childNodeID);
					
						// Only traverse tree down if the child is a service
						if ($this->NodeTypeIsService($childNode)) {
							$strNodeTreeDown .= $this->GetNodeTreeDown($childNodeID);
						}
					}
				}
							
			} else {
				$strNodeTreeDown .= 'ERROR: GetNodeTreeDown for ' . $parentID . ':';
				$strNodeTreeDown .= ' childList is NO ARRAY!';
			}
		}

		return $strNodeTreeDown;
	}

	/**
	 * Generate the text to generate the Graphviz image from
	 *
	 * @since 1.8
	 *
	 * @param array $startNodes
	 *
	 * @return string
	 */
	protected function GetNodeTreeUp( $childNodeID ) {
		$strNodeTreeUp = '';

		// Get the parents
		$parentNodeIDList = $this->getParentNodeList( $childNodeID );
		$strNodeTreeUp .= $this->prettyPrintArray($parentNodeIDList, 'parentNodeIDList for ' . $childNodeID . ' : ');
		
		// Recursively traverse all parents found
		foreach ($parentNodeIDList as $parentNodeID) {

			// Get the parentNode
			$parentNode = $this->getSOAVisNode( $parentNodeID );
			
			// Format the node type for this parent
			$strNodeTreeUp .= $this->SetNodeFormatting($parentNodeID, $this->mSOAVisNodeDirectionUp);

			// Make the edge between the parent and this child service
			$strNodeTreeUp .= $this->MakeNodeTreeEdge($parentNodeID, $childNodeID);

			// Traverse tree up
			$strNodeTreeUp .= $this->GetNodeTreeUp($parentNodeID);

		}

		return $strNodeTreeUp;
	}


	/**
	 * Generate the text for a SOAVisNode
	 *
	 * @since 1.8
	 *
	 * @param array $startNodes
	 *
	 * @return string
	 */
	protected function GetNodeFormatting( $serviceNode ) {

		$strNodeFormat = '';
		
		// Get the node for this serviceNode
		if (is_array($serviceNode)) {
			$SOAVisNode = $serviceNode;
		} else {
			$SOAVisNode = $this->getSOAVisNode( $serviceNode );
		}
		if ($SOAVisNode == null) {
			return 'ERROR: GetNodeFormatting: No node provided for ' . $serviceNode . '!<br/>';
		}
		$serviceNodeID = $SOAVisNode[$this->mSOAVisNodeNodeID];
		$serviceNodeName = $SOAVisNode[$this->mSOAVisNodeNodeName];
		$serviceUrlID = $SOAVisNode[$this->mSOAVisNodeUrl];
		
		// Only set the node type when it needs to be drawn
		if (!$this->NodeTypeIsToBeDrawn($SOAVisNode)) {
			return '';
		}
			
		// Start formatting string
		$strNodeFormat .= $serviceNodeName;
		$strNodeFormat .= self::$mSOAVisNodeFormatStart;

		// Add the url to the details for this node
		$strNodeFormat .= sprintf(self::$mGraphVizImageRenderURL, $serviceUrlID);
		
		// Get some formatting parameters
		$strNodeFormat .= $this->GetNodeFormatColor($SOAVisNode);
		$strNodeFormat .= $this->GetNodeFormatFillColor($SOAVisNode);
		$strNodeFormat .= $this->GetNodeFormatFontColor($SOAVisNode);
		$strNodeFormat .= $this->GetNodeFormatShape($SOAVisNode);
		
		// Terminate formatting string
		$strNodeFormat .= self::$mSOAVisNodeFormatDefaultLast;
		$strNodeFormat .= self::$mSOAVisNodeFormatEnd;

		return $strNodeFormat;
	}

	/**
	 * Generate the color for a SOAVisNode
	 *
	 * @since 1.8
	 *
	 * @param $SOAVisNode
	 *
	 * @return string
	 */
	protected function GetNodeFormatColor( $SOAVisNode ) {

		$strColorFormat = '';
		
		// Get color format depending on parameters provided
		$strColorFormat = $this->getParamMapValue($this->mSOAVisNodeColorColumn, $this->mColorMap, $SOAVisNode);
		if ($strColorFormat) {
			return $strColorFormat;
		}
		
		return $strColorFormat;
		
	}

	/**
	 * Generate the FillColor for a SOAVisNode
	 *
	 * @since 1.8
	 *
	 * @param $SOAVisNode
	 *
	 * @return string
	 */
	protected function GetNodeFormatFillColor( $SOAVisNode ) {

		$strFillColorFormat = '';
		
		// Get fillColor format depending on parameters provided
		$strFillColorFormat = $this->getParamMapValue($this->mSOAVisNodeFillColorColumn, $this->mFillColorMap, $SOAVisNode);
		if ($strFillColorFormat) {
			return $strFillColorFormat;
		}
		
		// Set different color for service or other node type
		if ($this->NodeTypeIsService($SOAVisNode)) {
			
			// Set fill color depending on direction for services only
			$nodeDirection =  $SOAVisNode[$this->mSOAVisNodeDirection];
			switch ($nodeDirection) {
				case $this->mSOAVisNodeDirectionStart:
					$strFillColorFormat .= self::$mSOAVisNodeFormatFillColorStart;
					break;
				case $this->mSOAVisNodeDirectionDown:
					$strFillColorFormat .= self::$mSOAVisNodeFormatFillColorDown;
					break;
				case $this->mSOAVisNodeDirectionUp:
					$strFillColorFormat .= self::$mSOAVisNodeFormatFillColorUp;
					break;
				default:
					$strFillColorFormat .= self::$mSOAVisNodeFormatFillColorDefault;
					break;
			}
		} else {
					
			// Set fill color depending on direction for services only
			$strFillColorFormat .= self::$mSOAVisNodeFormatFillColorDefault;
		}
		
		return $strFillColorFormat;
		
	}

	/**
	 * Generate the FontColor for a SOAVisNode
	 *
	 * @since 1.8
	 *
	 * @param $SOAVisNode
	 *
	 * @return string
	 */
	protected function GetNodeFormatFontColor( $SOAVisNode ) {

		$strFontColorFormat = '';
		
		// Get fontColor format depending on parameters provided
		$strFontColorFormat = $this->getParamMapValue($this->mSOAVisNodeFontColorColumn, $this->mFontColorMap, $SOAVisNode);
		if ($strFontColorFormat) {
			return $strFontColorFormat;
		}
		
		return $strFontColorFormat;
		
	}
	
	/**
	 * Generate the color for a SOAVisNode
	 *
	 * @since 1.8
	 *
	 * @param $SOAVisNode
	 *
	 * @return string
	 */
	protected function GetNodeFormatShape( $SOAVisNode ) {

		$strShapeFormat = '';
		
		// Get shape format depending on parameters provided
		$strShapeFormat = $this->getParamMapValue($this->mSOAVisNodeShapeColumn, $this->mShapeMap, $SOAVisNode);
		if ($strShapeFormat) {
			return $strShapeFormat;
		}

		// Set different color for service or other node type
		if ($this->NodeTypeIsService($SOAVisNode)) {
			$strShapeFormat .= self::$mSOAVisNodeFormatShapeService;
		} else {
					
			// Set node shape for system depending on direction
			if ($nodeDirection == $this->mSOAVisNodeDirectionUp) {
				$strShapeFormat .= self::$mSOAVisNodeFormatShapeSystemUp;
			} else {
				$strShapeFormat .= self::$mSOAVisNodeFormatShapeSystemDown;
			}
		}
		
		return $strShapeFormat;
		
	}
	
	/**
	 * Generate the text for a SOAVisNode
	 *
	 * @since 1.8
	 *
	 * @param array $startNodes
	 *
	 * @return string
	 */
	protected function SetNodeFormatting( $searchNodeID, $nodeDirection ) {

		$strNodeFormat = '';
		
		// Find the node to display
		foreach ($this->mSOAVisNodes as &$currentNode) {
			if ($currentNode[$this->mSOAVisNodeNodeID] == $searchNodeID) {
				$currentNode[$this->mSOAVisNodeToDraw] += 1;
				if (!($currentNode[$this->mSOAVisNodeDirection] == $this->mSOAVisNodeDirectionStart)) {
					$currentNode[$this->mSOAVisNodeDirection] = $nodeDirection;
				}
				return '';
			}
		}
		return '';
		
	}
	
	
	/**
	 * Generate the text to generate the Graphviz image from
	 *
	 * @since 1.8
	 *
	 * @param array $startNodes
	 *
	 * @return string
	 */
	protected function MakeNodeTreeEdge( $parentNodeID, $childNodeID ) {

		$searchID = $parentNodeID . '-' . $childNodeID;
		
		// Find the edge to display
		foreach ($this->mSOAVisEdges as &$currentEdge) {
			if ($currentEdge[$this->mSOAVisNodeEdgeID] == $searchID) {
				$currentEdge[$this->mSOAVisNodeToDraw] += 1;
				return '';
			}
		}
		return '';
	}


	/**
	 * Generate the text for all edges to generate the Graphviz image from
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	protected function GetNodeTreeNodeText( ) {
		$strNodeTreeNode = '';
		
		foreach ($this->mSOAVisNodes as $currentNode) {
			if ($currentNode[$this->mSOAVisNodeToDraw] > 0) {
				$strNodeTreeNode .= $this->GetNodeFormatting($currentNode);
			}
		}

		return $strNodeTreeNode;
	}

	/**
	 * Generate the text for all edges to generate the Graphviz image from
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	protected function GetNodeTreeEdgeText( ) {
		$strNodeTreeEdge = '';
		
		foreach ($this->mSOAVisEdges as $currentEdge) {
			$parentName = $currentEdge[$this->mSOAVisNodeNodeName];
			$childName = $currentEdge[$this->mSOAVisNodeChildName];
			if ($currentEdge[$this->mSOAVisNodeToDraw] > 0) {
				$strNodeTreeEdge .= $parentName . self::$mGraphVizImageRenderEdge . $childName . self::$mGraphVizImageRenderEdgeEnd;
			}
		}

		return $strNodeTreeEdge;
	}
	
	/**
	 * Get the list of SOAVisNodes where the searchIDType equals the searchID
	 *
	 * @since 1.8
	 *
	 * @param $searchID
	 * @param $searchIDType
	 *
	 * @return array $serviceNodeList
	 */
	protected function getParentNodeList( $childID ) {
		
		$nodeList = array ();
		
		foreach ( $this->mSOAVisEdges as $SOAVisEdge ) {
			$nodeID = $SOAVisEdge[$this->mSOAVisNodeNodeID];
			$childNodeID = $SOAVisEdge[$this->mSOAVisNodeChildID];
			if ( $childNodeID == $childID) {
				$nodeList[] = $nodeID;
			}
		}
		
		return $nodeList;
	
	}

	/**
	 * Get the type of the $SOAVisNode provided as parameter
	 *
	 * @since 1.8
	 *
	 * @param $SOAVisNode
	 *
	 * @return string
	 */
	protected function GetNodeType( $SOAVisNode ) {
		if ($SOAVisNode && isset($SOAVisNode[$this->mSOAVisNodeType])) {
			return $SOAVisNode[$this->mSOAVisNodeType];
		}
		return null;
	}


	/**
	 * Check whether this node is a service
	 *
	 * @since 1.8
	 *
	 * @param $SOAVisNode
	 *
	 * @return boolean
	 */
	protected function NodeTypeIsService( $SOAVisNode ) {
		// A service is only a service if the type is Service
		if ($SOAVisNode) {
			return $this->GetNodeType($SOAVisNode) == $this->mSOAVisNodeTypeService;
		}
		return false;
	}


	/**
	 * Only Service and System nodes may be drawn
	 *
	 * @since 1.8
	 *
	 * @param $SOAVisNode
	 *
	 * @return boolean
	 */
	protected function NodeTypeIsToBeDrawn( $SOAVisNode ) {
		// Only services and systems can be drawn
		if ($SOAVisNode) {
			if ($this->GetNodeType($SOAVisNode) == $this->mSOAVisNodeTypeService) return true;
			if ($this->GetNodeType($SOAVisNode) == $this->mSOAVisNodeTypeSystem) return true;
		}
		return false;
	}


	/**
	 * Return paramName in all lower case letters
	 *
	 * @since 1.8
	 *
	 * @param string $paramName
	 *
	 * @return string
	 */
	public function getParamNameLower( $paramName ) {
		$lowerParamName = strtolower( trim( $paramName ) );
		return $lowerParamName;
	}

	/**
	 *
	 * @since 1.8
	 *
	 * @param $paramKey
	 * @param $paramName
	 * @param $SOAVisNode
	 *
	 * @return Value found in paramMap
	 */
	public function getParamMapValue( $paramKey, $paramMap, $SOAVisNode ) {
		
		$strParamMapValue = '';
		
		// Find the value to check in the list of SOAVisNode columns
		if(isset($SOAVisNode[$paramKey][0][$this->mSOAVisNodeNodeID])) {
			$SOAVisNodeColumnValue = $SOAVisNode[$paramKey][0][$this->mSOAVisNodeNodeID];
			if(array_key_exists($SOAVisNodeColumnValue,$paramMap)===true) {
				$strParamMapValue .= $paramMap[$SOAVisNodeColumnValue];
			}
		}

		return $strParamMapValue;
	}

	/**
	 *
	 * @since 1.8
	 *
	 * @param $paramName
	 *
	 * @return Value of parameter
	 */
	public function getParamValue( $paramName ) {
		if ($this->params[$this->normaliseValue( $paramName )]) {
			return trim( $this->params[$this->normaliseValue( $paramName )] );
		}
		return null;
	}
	

	/**
	 * Explodes a single string into an associative array.
	 *
	 * The string has a format like:
	 *   "label1=value1,label2=value2,..."
	 *
	 * The target will explodes into an array like:
	 *   $target['label1']='value1';
	 *   $target['label2']='value2';
	 *
	 * @param string $tmap   (byref) the string to explode and convert. The value is not modified
	 * @param string $tsep   (byval) the separator of each tuple.
	 * @param string $vsep   (byval) the separator of label and value in each tuple.
	 *
	 * @return array  $target the target array to explode to.
	 */
	protected function processParameterMap($tmap, $tsep = ',', $vsep = '=') {
		$tuples = explode($tsep,$tmap);
		$target = array();
	
		foreach($tuples as $tuple) {
			if(strpos($tuple,'=')===false) {
				$target[$this->normaliseValue($tuple)] = '';
			} else {
				list($label, $value) = split('=',$tuple,2);
				$target[$this->normaliseValue($label)] = $value;
			}
		}
		
		return $target;
	}
	

	/**
	 * Normalises a value to a string that can be used as identifier
	 *
	 * @param string $value   the value to replace.
	 *
	 * @return string  the normalised value.
	 */
	protected function normaliseValue( $value ) {
		$target = strtolower(trim($value));
		return str_replace( array( ' ', '_' ), '-', strip_tags( $target ) );
	}
				
	
	/**
	 *
	 * @since 1.8
	 *
	 * @param $array
	 * @param $name
	 * @param $keys
	 *
	 * @return string with array information
	 */
	private function prettyPrintArray( &$array, $name, $keys = array()) {
		$result = '';
		foreach($array as $key=>&$value) {
			if(is_array($value)===true) {
				$newkeys   = $keys;
				$newkeys[] = $key;
				$result   .= $this->prettyPrintArray($value,$name,$newkeys);
			} else {
				$result .= $name;
				foreach($keys as $label) {
					$result .= '[' . $label . ']';
				}
				// Here was the error...
				if (gettype($key) == 'object' ) {
					$result .= '   KEY is of class: ' . get_class($key) . '!<br/>';
					if (gettype($value) == 'object') {
						$result .= '   VALUE is of class: ' . get_class($value) . '!';
					} else {
						$result .= '...Value=' . $value . '...';
					}
					$result .= '<br/>';
				} else if (gettype($value) == 'object' ) {
					$result .= '[' . $key . '] = !' . serialize($value) . '!';
					$result .= '   VALUE is of class: ' . get_class($value) . '!<br/>';
				} else {
					$result .= '[' . $key . '] = !' . $value . '!';
					$result .= '<br/>';
				}
			}
		}
		return $result;
	}

	/**
	 * Process the parameters provided into local variables
	 *
	 * @since 1.8
	 *
	 * @param $definitions array of IParamDefinition
	 *
	 */
	protected function processParameters( array $params ) {

		global $wgUploadDirectory;       // Storage location of the shapefiles (if used)

		$this->m_graphName = $this->getParamValue ('graphname');
		$this->m_graphSize = $this->getParamValue ('graphsize');
	
		$this->m_rankdir = strtoupper( $this->getParamValue ('arrowdirection') );
		
		$this->m_nodeShape = $this->getParamValue ('nodeshape');
		$this->m_wordWrapLimit = $this->getParamValue ('wordwraplimit');

		$this->m_columnTo = $this->normaliseValue( $this->getParamValue ('columnTo') );
		

		// --- START NODES ---
		$parameterToProcess = $this->getParamValue ('startnodes');
		if ($parameterToProcess) {
			$values = explode(",",$parameterToProcess);
			foreach($values as $value) {
				// Add the node to the list of nodes to export, mark it as unprocessed and set the level to 0.
				if( strpos($value,'{{#') ) {
					// We must preprocess the string!
					//echo "<pre>Warning could not process unexpanded variable '".$value."'</pre>";
				} else {
					$this->mStartNodes[] = $this->normaliseValue($value);
				}
			}
		}
		
		// --- MAX LEVELS ---
		$parameterToProcess = $this->getParamValue ('maxlevels');
		if ($parameterToProcess) {
			// Copy and correct the value of maxlevels the range is [-1..*], where -1 means infinity
			// and all other numbers are themselves...
			$this->mMaxLevels = $parameterToProcess;
		
			if(is_numeric($this->mMaxLevels)) {
				if($this->mMaxLevels <= -1 ) {
					$this->mMaxLevels = -1;
				}
			} else {
				$this->mMaxLevels = -1;
			}
		
			// We default the maxlevel when there are no start nodes...
			if(count ($this->mStartNodes) <= 0)
				$this->mMaxLevels = -1;
		}
		
		// --- MAX UP LEVELS ---
		$parameterToProcess = $this->getParamValue ('maxuplevels');
		if ($parameterToProcess) {
			// Copy and correct the value of maxuplevels the range is [-1..*], where -1 means infinity
			// and all other numbers are themselves...
			$this->mMaxUpLevels = $parameterToProcess;
		
			if(is_numeric($this->mMaxUpLevels)) {
				if($this->mMaxUpLevels <= -1 ) {
					$this->mMaxUpLevels = -1;
				}
			} else {
				$this->mMaxUpLevels = -1;
			}
		
			// We default the maxuplevel when there are no start nodes...
			if(array_key_exists('startnodes', $this->params)===false)
				$this->mMaxUpLevels = -1;
		}
		
		// === MAPPINGS OF COLORS AND SHAPES ===
		
		// --- COLOR MAP ---
		$parameterToProcess = $this->getParamValue ('columncolor');
		if ($parameterToProcess) {
			$this->mSOAVisNodeColorColumn = $this->normaliseValue($parameterToProcess);
		}
		$parameterToProcess = $this->getParamValue ('colormap');
		if ($parameterToProcess) {
			$this->mColorMap = $this->processParameterMap($parameterToProcess);
				
			// Preprocess the ColorMap...
			// Properties may looks like this "colormap=design=gray,test=gray,build=gray,production=green,deprecated=yellow,obsolete=red,system=grey"
			foreach($this->mColorMap as $key=>$value) {
				$this->mColorMap[$key] = 'color=' . $value . ',';
			}
		}
		
		// --- FILL COLOR MAP ---
		$parameterToProcess = $this->getParamValue ('columnfillcolor');
		if ($parameterToProcess) {
			$this->mSOAVisNodeFillColorColumn = $this->normaliseValue($parameterToProcess);
		}
		$parameterToProcess = $this->getParamValue ('fillcolormap');
		if ($parameterToProcess) {
			$this->mFillColorMap = $this->processParameterMap($parameterToProcess);
		
			// Preprocess the fillColorMap...
			// Properties may looks like this "fillcolormap=design=gray,test=gray,build=gray,production=green,deprecated=yellow,obsolete=red,system=grey"
			foreach($this->mFillColorMap as $key=>$value) {
				$this->mFillColorMap[$key] = 'fillcolor=' . $value . ',';
			}
		}
			
		// --- FONT COLOR MAP ---
		$parameterToProcess = $this->getParamValue ('columnfontcolor');
		if ($parameterToProcess) {
			$this->mSOAVisNodeFontColorColumn = $this->normaliseValue($parameterToProcess);
		}
		$parameterToProcess = $this->getParamValue ('fontcolormap');
		if ($parameterToProcess) {
			$this->mFontColorMap = $this->processParameterMap($parameterToProcess);
		
			// Preprocess the fontColorMap...
			// Properties may looks like this "fontcolormap=design=gray,production=green,deprecated=yellow,obsolete=red"
			foreach($this->mFontColorMap as $key=>$value) {
				$this->mFontColorMap[$key] = 'fontcolor=' . $value . ',';
			}
		}
		
		// --- SHAPE MAP ---
		$parameterToProcess = $this->getParamValue ('columnshape');
		if ($parameterToProcess) {
			$this->mSOAVisNodeShapeColumn = $this->normaliseValue($parameterToProcess);
		}
		$parameterToProcess = $this->getParamValue ('shapemap');
		if ($parameterToProcess) {
			$this->mShapeMap = $this->processParameterMap($parameterToProcess);

			// Preprocess the shapemap...
			// Properties may looks like this "shapemap=Category:Service=box,Category:Unit=png|filename.png"
			foreach($this->mShapeMap as $key=>$value) {
				if(strpos($value,'!')) {
					list($type,$filename) = explode('!',$value,2);
					$this->mShapeMap[$key] = 'shape=' . $type . ', shapefile="' . $wgUploadDirectory . '/' . $filename . '",';
				} else {
					$this->mShapeMap[$key] = 'shape=' . $value . ',';
				}
			}
		}
		
		// === DEFAULTS FOR COLORS AND SHAPES ===
		$this->mDefaultColor = $this->getParamValue ('defaultcolor');
		$this->mDefaultFillColor = $this->getParamValue ('defaultfillcolor');
		$this->mDefaultFontColor = $this->getParamValue ('defaultfontcolor');
		$this->mStartFillColor = $this->getParamValue ('startfillcolor');
		$this->mStartFontColor = $this->getParamValue ('startfontcolor');
		$this->mStartColor = $this->getParamValue ('startcolor');

		// --- DEFAULT SHAPE ---
		$parameterToProcess = $this->getParamValue ('defaultshape');
		if ($parameterToProcess) {
			$this->mDefaultShape = $parameterToProcess;
		
			if(strpos($this->mDefaultShape,'!')) {
				list($type,$filename) = explode('!',$this->mDefaultShape,2);
				$this->mDefaultShape = 'shape='.$type.', shapefile="'.$wgUploadDirectory.'/'.$filename.'"';
			} else {
				$this->mDefaultShape = 'shape='.$this->mDefaultShape;
			}
		}
		
		
	}
	
	/**
	 * @see SMWResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * @param $definitions array of IParamDefinition
	 *
	 * @return array of IParamDefinition|array
	 */
	public function getParamDefinitions( array $definitions ) {
		$params = parent::getParamDefinitions( $definitions );

		$params[$this->normaliseValue('graphName')] = array(
			'default' => 'QueryResult',
			'message' => 'srf_paramdesc_graphname',
		);

		$params[$this->normaliseValue('graphSize')] = array(
			'type' => 'integer',
			'default' => '',
			'message' => 'srf_paramdesc_graphsize',
			'manipulatedefault' => false,
		);
		
		$params[$this->normaliseValue('startColor')] = array(
			'type' => 'string',
			'default' => 'yellow',
			'message' => 'srf_paramdesc_soagraph_startcolor',
		);

		$params[$this->normaliseValue('arrowDirection')] = array(
			'aliases' => 'rankdir',
			'default' => 'TB',
			'message' => 'srf_paramdesc_rankdir',
			'values' => array( 'LR', 'RL', 'TB', 'BT' ),
		);

		$params[$this->normaliseValue('nodeShape')] = array(
			'default' => false,
			'message' => 'srf-paramdesc-soagraph-nodeshape',
			'manipulatedefault' => false,
			'values' => self::$NODE_SHAPES,
		);

		$params[$this->normaliseValue('wordWrapLimit')] = array(
			'type' => 'integer',
			'default' => 25,
			'message' => 'srf-paramdesc-soagraph-wwl',
			'manipulatedefault' => false,
		);

		$params[$this->normaliseValue('maxlevels')] = array(
			'type' => 'string',
			'default' => '-1',
			'message' => 'srf-paramdesc-soagraph-maxlevels',
		);

		$params[$this->normaliseValue('maxuplevels')] = array(
			'type' => 'string',
			'default' => '-1',
			'message' => 'srf-paramdesc-soagraph-maxuplevels',
		);
		
		$params[$this->normaliseValue('columnTo')] = array(
			'type' => 'string',
			'default' => '',
			'message' => 'srf-paramdesc-soagraph-columnto',
		);

		$params[$this->normaliseValue('startNodes')] = array(
			'type' => 'string',
			'default' => '',
			'message' => 'srf-paramdesc-soagraph-startnodes',
		);

		$params[$this->normaliseValue('columnFillColor')] = array(
			'type' => 'string',
			'default' => '',
			'message' => 'srf-paramdesc-soagraph-columnfillcolor',
		);

		$params[$this->normaliseValue('fillColorMap')] = array(
				'type' => 'string',
				'default' => '',
				'message' => 'srf-paramdesc-soagraph-fillcolormap',
		);

		$params[$this->normaliseValue('columnFontColor')] = array(
			'type' => 'string',
			'default' => '',
			'message' => 'srf-paramdesc-soagraph-columnfontcolor',
		);

		$params[$this->normaliseValue('fontColorMap')] = array(
				'type' => 'string',
				'default' => '',
				'message' => 'srf-paramdesc-soagraph-fontcolormap',
		);
		
		$params[$this->normaliseValue('defaultColor')] = array(
			'type' => 'string',
			'default' => '',
			'message' => 'srf-paramdesc-soagraph-defaultcolor',
		);

		$params[$this->normaliseValue('defaultFillColor')] = array(
				'type' => 'string',
				'default' => '',
				'message' => 'srf-paramdesc-soagraph-defaultfillcolor',
		);

		$params[$this->normaliseValue('defaultFontColor')] = array(
				'type' => 'string',
				'default' => '',
				'message' => 'srf-paramdesc-soagraph-defaultfontcolor',
		);
		
		$params[$this->normaliseValue('defaultShape')] = array(
				'type' => 'string',
				'default' => '',
				'message' => 'srf-paramdesc-soagraph-defaultshape',
		);
		
		$params[$this->normaliseValue('columnColor')] = array(
			'type' => 'string',
			'default' => '',
			'message' => 'srf-paramdesc-soagraph-columncolor',
		);

		$params[$this->normaliseValue('colorMap')] = array(
			'type' => 'string',
			'default' => '',
			'message' => 'srf-paramdesc-soagraph-colormap',
		);

		$params[$this->normaliseValue('startFillColor')] = array(
			'type' => 'string',
			'default' => '#ffffff',
			'message' => 'srf-paramdesc-soagraph-startfillcolor',
		);

		$params[$this->normaliseValue('startFontColor')] = array(
			'type' => 'string',
			'default' => '#ffffff',
			'message' => 'srf-paramdesc-soagraph-startfontcolor',
		);
		
		$params[$this->normaliseValue('startColor')] = array(
			'type' => 'string',
			'default' => '#0000aa',
			'message' => 'srf-paramdesc-soagraph-startcolor',
		);

		$params[$this->normaliseValue('startShape')] = array(
			'type' => 'string',
			'default' => 'shape=box',
			'message' => 'srf-paramdesc-soagraph-startshape',
		);

		$params[$this->normaliseValue('columnShape')] = array(
			'type' => 'string',
			'default' => '',
			'message' => 'srf-paramdesc-soagraph-columnshape',
		);

		$params[$this->normaliseValue('shapeMap')] = array(
			'type' => 'string',
			'default' => '',
			'message' => 'srf-paramdesc-soagraph-shapemap',
		);

		$params[$this->normaliseValue('columnlabel')] = array(
			'type' => 'string',
			'default' => '',
			'message' => 'srf-paramdesc-soagraph-columnlabel',
		);

		$params[$this->normaliseValue('debug')] = array(
			'type' => 'string',
			'default' => '',
			'message' => 'srf-paramdesc-soagraph-debug',
		);

		$params[$this->normaliseValue('limit')] = array(
			'type' => 'string',
			'default' => '',
			'message' => 'srf-paramdesc-soagraph-limit',
		);

		return $params;
	}
	
}
