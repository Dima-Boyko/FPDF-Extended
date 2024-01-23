<?php

/************************************************************************
* FPDF_ETD (based FPDF)
************************************************************************/
/*
*Автор:Boyko Dmitry
*Дата:16.04.2021
*Сайт:www.itdix.net
*E-mail:tmgsoft@hotmail.com
*Описание: дополнение для библиотеки FPDF
*/

class FPDF_ETD extends FPDF
{
	protected $cell_pages;
	protected $RowCells=Array();
	protected $RowHeight=0;
	
	function Row($max_height=0,$border=true,$next_line=true){
		
		$height=$max_height;
		if($max_height<1){
			$height = $this->RowHeight;
		}

		$count=count($this->RowCells );
		$i=0;
		foreach($this->RowCells as $cell){
			
			
			$li=false;
			if($max_height<1){
				$li=$cell['line_indent'];
			}
			
			$style=Array(
				'line_indent'=>$li,
				'padding'=>$cell['fill'],
				'border'=>$border,
				'fill'=>$cell['fill'],
				'nl'=>false
			);
			
			if($next_line){
				$i++;
				if($count<=$i)$style['nl']=true;
			}
			
			
			$this->UCell($cell['string_split'],$cell['align'],$cell['width'],$height,$style);
		}
		
		$this->RowClear();

	}

	function AddCell($string='',$a='L',$w=100,$style=Array()){
		
		$height=0; //ширина ячеки кчлм есть межстрочнй оступ
		$li=false;     //межстрочный оступ
		$p=0;
		$fill=false;
		
		
		
		if(!empty($style['li']))$li=$style['li'];
		if(!empty($style['line_indent']))$li=$style['line_indent'];
		if(!empty($style['padding']))$p=$style['padding'];
		if(!empty($style['fill']))$fill=$style['fill'];
		
		$wp=$w-$p*2;
		
		$string=strip_tags($string);
		$string=htmlspecialchars_decode($string);
		$string=str_replace('&nbsp;',' ',$string);
		
		$string_split=$this->StringSplit($string,$wp);
		
		if($li){
			$height = $li * count($string_split);
		}else{
			$height = $this->FontSizePt * count($string_split);
		}
		
		if($this->RowHeight < $height) $this->RowHeight = $height;
		
		$data=Array(
			'string'=>$string,
			'string_split'=>$string_split,
			'align'=>$a,
			'width'=>$w,
			'width_padding'=>$wp,
			'height'=>$height,
			'line_indent'=>$li,
			'padding'=>$p,
			'fill'=>$fill
		);

		
		
		$data=array_merge($data,$style);
		$this->RowCells[]=$data;
	}
	
	function RowClear(){
		$this->RowCells=Array();
	}
	
	/*
	Universal Cell
	FPDF Ячейка которая может правльно перносить текст
	---------------------------------------------------------------------------------
	$string - тектс в ячейке
	$a -выравнивание L ,R ,C 
	$w - ширина ячейки
	$h - высота ячейки
	$style - масив стилей
	---------------------------------------------------------------------------------
	*/
	
	function UCell($string='',$a='L',$w=100,$h=60,$style=Array())
	{
		/*
		$line_indent ($li) - если больше 0 то задаеться межчтрочный отсуп и ячейка по высоте не фиксированая
		$padding - отсуп по бокам
		$border - бордер
		$fill - закрашеный фон
		$ln - позиция для следуцющего блока 0 - в одном рядку 1 - следующая строка
		$next_line ($nl) - переносить на следующую строку
		*/
		
		//Default style
		$li=0;
		$p=0;
		$border=false;
		$fill=false;
		$ln=false;
		
		if(!empty($style['line_indent']))$li=$style['line_indent'];
		if(!empty($style['li']))$li=$style['li'];
		if(!empty($style['padding']))$p=$style['padding'];
		if(!empty($style['border']))$border=$style['border'];
		if(!empty($style['fill']))$fill=$style['fill'];
		if(!empty($style['next_line']))$ln=$style['next_line'];
		if(!empty($style['nl']))$ln=$style['nl'];
		
		
		
		$page=$this->page;
		$PageEnd=$this->PageBreakTrigger;


		
		$x=$this->GetX();
		$y=$this->GetY();
		$rh=$h;
		$wp=$w-$p*2;
		
		if(is_array($string)){
			$Rows=$string;
		}else{
			$string=strip_tags($string);
			$string=htmlspecialchars_decode($string);
			$string=str_replace('&nbsp;',' ',$string);
			$Rows=$this->StringSplit($string,$wp);
		}
		
		
		$RCount=count($Rows);
		
		if(!$Rows)return true;
		

		if($li==0){//фиксированя высота ячеки
			$rh=$h/$RCount;

		}else{//высота ячейки постраиваеться под текст
			$rh=$li;
			$h=$rh*$RCount;
		}
		
		
		


		$this->SetXY($x,$y);
		$last_rh=0;
		foreach($Rows as $key=>$str){
			//рисование рамки
			$border_style='';
			if($border){
				$border_style='LR';//рамки внутрених блоков
				if($key == 0)$border_style='TLR';//верхний блок
				if($key == $RCount-1)$border_style='LRB';//нижний блок
				if($RCount==1)$border_style='TLBR';//если только 1 строка
			}
			$this->Cell($w,$rh - $last_rh,$str,$border_style,2,$a,$fill);
			$last_rh=0;
			if($this->y + $rh >= $PageEnd){
				
				$last_rh = $PageEnd - $this->y;
				if($last_rh>0){
					if($border)$border_style='LR';
					$this->Cell($w,$last_rh,'',$border_style,2,$a,$fill);
					$last_rh-=$this->FontSizePt;

				}
				
				
				//Не создавать существующую страницу
				if($page < $this->cell_pages){

					$this->page=$page+1;
					$this->SetXY($x,$this->lMargin);
				}
				

				

			}
			

		}
		
		$this->cell_pages=$this->page;
		
		if($ln){
			if($this->page==$page){
				$this->SetXY($this->lMargin,$y+$h-1);
			}else{
				$this->SetX($this->lMargin);
			}
			
		}else{
			if($this->page!=$page){
				$this->page=$page;
			}
			$this->SetXY($x+$w,$y);
		}
		
		
		return true;
	}
	
	
	//Разделение текста на строки чтобы он поместился с размер width ячейки
	function StringSplit($string='',$w){
		
		$Rows=Array();
		$string=str_replace('	','   ',$string);
		$string=str_replace(chr(10),'',$string);
		
		$len=strlen($string);
		
		if($len==0)return false;
		
		$wlen=$this->GetStringWidth($string);
		if($wlen<$w){
			$Rows[]=$string;
		}else{
			
			$r=0;
			$i=0;
			$sp=0;//позиция пробела
			while($i<$len){

				if(ord(mb_substr($string,$i,1))==13){//новая строка если есть перносы

					$str=mb_substr($string,$r,$i-$r);
					$r=++$i;
					$Rows[]=$str;
					
					continue;
				}
				
				$str=mb_substr($string,$r,$i-$r);
				$slen=$this->GetStringWidth($str);
				
				if($slen>=$w){
					
					$sp=mb_strripos($str,' ');
					if($sp!=0){
						$i=$r+$sp;
						$str=mb_substr($string,$r,$i-$r);
					}
					$r=$i;
					$Rows[]=$str;
					continue;
					
				}
				$i++;
			}

			
			if(strlen($str)>0)$Rows[]=$str;
			

		}
	
		return $Rows;
	}
	
	function GetPage(){
		return $this->page;
	}
	function SetPage($page=1){
		$this->page=intval($page);
		return true;
	}
	function GetPageBreakTrigger(){
		return $this->PageBreakTrigger;
	}
	
	function GetMarginTop(){

		return $this->tMargin;
	}
	
	function GetMarginLeft(){
		return $this->lMargin;
	}
	
	function GetMarginRight(){
		return $this->rMargin;
	}
}
?>