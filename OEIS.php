<?php

/**
 * Class that extracts data from main OEIS entry for a given sequence
 *
 * @author Anastas Stoyanovsky
 * @date 21 Dec 2013
 */
class OEIS
{
  protected $id;
  protected $sequence;
  protected $description;
  protected $data;

  /**
   * Constructor; takes only OEIS sequence number as (required) argument.
   *
   * @param string $sequence OEIS sequence ID, e.g. A000108
   * @return OEIS
   */
  public function __construct($sequence)
  {
    $doc = new DOMDocument();
    $html = file_get_contents("http://oeis.org/" . $_GET['sequence']);

    // We're going to convert it to XML; define what tags are allowed
    // - the simple markup used by OEIS makes this possible
    $html = strip_tags($html, '<html><body><table><tr><td><p><a>');

    // After this we'll match anchor tags with regex; due to greedy matching,
    // ensure each anchor is on its own line
    $html = str_replace("<a","\n<a",$html);

    // Escape the anchor tags. This is a bad practice, but it works here.
    $html = preg_replace('/<a(.*)>(.*)<\/a>/', '&lt;a$1&gt;$2&lt;/a&gt;',$html);

    // DOMDocument object
    $doc->loadHTML($html);

    // Have DOMDocument object spit out XML for a SimpleXML object
    $xmlDoc = new SimpleXMLElement($doc->saveXML());

    // This sort of typecasting is necessary because, for some reason,
    // doing $xmlDoc->body->...->table[1]->tr[2] doesn't work.
    $base = (array)$xmlDoc->body->table[1]->tr->td->table[1];
    $base = (array)$base['tr'];
    
    $this->head = $base[2];
    $this->sequence = $base[3];
    $this->body = $base[4];

    $this->process_head();
    $this->process_sequence();
    $this->process_body();
  }

  /**
   * Processes "header" of OEIS page, which contains the sequence description
   */
  private function process_head()  
  {
    $tmp = (array)$this->head->td->table->tr->td;
    $this->id = $tmp[0];
    $this->description = $tmp[1];
    $this->something = $tmp[1]; // Not sure what, exactly this is
  }

  /**
   * Extracts the first few terms of the sequence as displayed on its main OEIS page
   */
  private function process_sequence()
  {
    $tmp = (array)$this->sequence->td->table->tr->td;
    $this->sequence = trim(preg_replace('/[[:space:]]+/',' ',$tmp[1]));
  }

  /**
   * Extracts the rest of the data related to this sequence from its main OEIS page
   */
  private function process_body()
  {
    // Initial data extraction
    $tmp = (array)$this->body->td->table;
    $tmp = $tmp['tr'];
    $data = array();
    foreach($tmp as $obj)
      {
	$objData = (array)$obj->td;
	$data[trim($objData[1])] = (array)$objData[2]->p;
      }

    // Run through the loop again to sanitize data, e.g. to remove data on HTML attrs
    $this->data = array();
    foreach($data as $key => $valueArray)
      {
	// We don't care about OEIS' formatting
	unset($valueArray['@attributes']);

	// Unsetting an array element can leave a key pointing nowhere; workaround
	$valueArray = array_values($valueArray);
	foreach($valueArray as $value)
	  {
	    // Undo the "new line for each anchor" regex from constructor, and trim
	    $this->data[$key][] = trim(str_replace("\n",' ',$value));
	  }
      }
  }

  /**
   * Gets sequence ID, e.g. A000108
   *
   * @return string
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Gets first few terms of sequence as displayed on its main OEIS page
   *
   * @return string
   */
  public function getSequence()
  {
    return $this->sequence;
  }

  /**
   * Gets description of sequence as on its main OEIS page
   *
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }

  /**
   * Gets sequence data extracted from main section of its main OEIS page
   *
   * @return array
   */
  public function getData()
  {
    return $this->data;
  }
}