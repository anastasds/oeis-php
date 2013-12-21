<?php

class OEIS
{
  protected $id;
  protected $sequence;
  protected $description;
  protected $data;

  public function __construct($sequence)
  {
    $doc = new DOMDocument();
    $html = file_get_contents("http://oeis.org/" . $_GET['sequence']);
    $html = strip_tags($html, '<html><body><table><tr><td><p><a>');
    $html = str_replace("<a","\n<a",$html);
    $html = preg_replace('/<a(.*)>(.*)<\/a>/', '&lt;a$1&gt;$2&lt;/a&gt;',$html);
    $doc->loadHTML($html);
    $xmlDoc = new SimpleXMLElement($doc->saveXML());

    
    $base = (array)$xmlDoc->body->table[1]->tr->td->table[1];
    $base = (array)$base['tr'];
    
    $this->head = $base[2];
    $this->sequence = $base[3];
    $this->body = $base[4];

    $this->process_head();
    $this->process_sequence();
    $this->process_body();
  }

  private function as_array($obj)
  {
    return (array)get_object_vars(json_decode(json_encode($obj)));
  }

  private function process_head()  
  {    
    $tmp = (array)$this->head->td->table->tr->td;
    $this->id = $tmp[0];
    $this->description = $tmp[1];
    $this->something = $tmp[1];
  }

  private function process_sequence()
  {
    $tmp = (array)$this->sequence->td->table->tr->td;
    $this->sequence = trim(preg_replace('/[[:space:]]+/',' ',$tmp[1]));
  }

  private function process_body()
  {
    $tmp = (array)$this->body->td->table;
    $tmp = $tmp['tr'];
    $data = array();
    foreach($tmp as $obj)
      {
	$objData = (array)$obj->td;
	$data[trim($objData[1])] = (array)$objData[2]->p;
      }

    $this->data = array();
    foreach($data as $key => $valueArray)
      {
	unset($valueArray['@attributes']);
	$valueArray = array_values($valueArray);
	foreach($valueArray as $value)
	  {
	    $this->data[$key][] = trim(str_replace("\n",' ',$value));
	  }
      }
  }

  public function getId()
  {
    return $this->id;
  }

  public function getSequence()
  {
    return $this->sequence;
  }

  public function getDescription()
  {
    return $this->description;
  }

  public function getData()
  {
    return $this->data;
  }
}