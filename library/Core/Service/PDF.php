<?php
class Core_Service_PDF
{
    private $_pdf = null;
    
    public function __construct($filename=null)
    {
        if(is_null($filename))
        {
            $this->_pdf = new Zend_Pdf();
        }
        else
        {
            $this->_pdf = Zend_Pdf::load($filename);
            
        }
    }
    
    public function getTitle()
    {
        if(isset($this->_pdf->properties['Title']))
        {
            return $this->_pdf->properties['Title'];
        }
        return '';
    }
    
    public function getAuthor()
    {
        if(isset($this->_pdf->properties['Author']))
        {
            return $this->_pdf->properties['Author'];
        }
        return '';
    }
    
    public function getSubject()
    {
        if(isset($this->_pdf->properties['Subject']))
        {
            return $this->_pdf->properties['Subject'];
        }
        return '';
    }
    
    public function getPageCount()
    {
        return count($this->_pdf->pages);
    }
    
    /**
     *
     * @param int $pageno
     * @return Zend_Pdf_Page
     */
    public function getPage($pageno)
    {
        if(!isset($this->_pdf->pages[$pageno-1]))
        {
            return false;
        }
        return $this->_pdf->pages[$pageno-1];
    }
}