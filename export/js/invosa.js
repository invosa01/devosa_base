// script buatan Invosa -- :))
// author : Yudi K.
// start  : March 2006
var strDateDelimiter = "-";
/* fungsi untuk ngecek apakah berada dalam frame atau gak */
// true jika tidak dalam frame
function isNoFrame() {
  return (window.top == window.self);
} // isNoFrame


function htmlentities( s ){
    var div = document.createElement('div');
    var text = document.createTextNode(s);
    div.appendChild(text);
    return div.innerHTML;
}

// fungsi untuk open window, dengan content berdasarkan content suatu ID
function openWindowById(el)
{
  var obj = document.getElementById(el);
  str = (obj != undefined) ? obj.innerHTML : "";

  kiri = (screen.width / 2) - 200;
  atas = (screen.height / 2) - 100;
  properti = "width=350, height=200, resizable=no, statusbar=no,menubar=no, titlebar=no, top=" + atas + ", left=" + kiri;

  wdw = window.open("","",properti);
  wdw.document.write("<html>");
  wdw.document.write("<body style=\"font-size:10px;font-family='Arial, Helvetica'\">");
  wdw.document.write(str);
  wdw.document.write("</body></html>");
}

// fungsi untuk melakukan validasi input, sesuai tipenya, dalam satu form, -> objForm (misal: document.form1)
// tipe yang divalidasi, dicek berdasarkan kelasnya
// yang diperiksa adalah input yang tidak disable dan tidak readonly, untuk semetara hanya handle text, select dan textarea
// -> numeric : tipe numeric, gak boleh kosong
// -> string: tipe string, gak boleh kosong
// -> string-empty: tipe string, boleh kosong
// -> date : tipe tanggal (format YYYY-MM-DD), gak boleh kosong
// -> date: tipe tanggal, boleh kosong
// -> time: tipe waktu (format HH:MM), gak boleh kosong
// -> time-empty: tipe waktu, boleh kosong
function validateForm(objForm)
{
  var bolResult = true;
  var obj = objForm;
  if (typeof obj.name == "undefined") {
    alert("Form is not defined!");
    return false;
  }
  x = "";
  total = obj.elements.length;
  for (i = 0; i < total; i++) {
    el = obj.elements[i];
    if (!el.disabled && !el.readonly) {
      if (el.type != "submit" && el.type != "reset" && el.type != "button" && el.type != "hidden" && el.type != "checkbox" && el.type != "radio") {
        strClass = el.className;
        if (strClass == "numeric") {
          // cek apakah angka, kosong atau tidak
          if (isNaN(el.value) || el.value == "") {
            alert("Error numeric data!!!");
            el.focus();
            return false;
          }
        } else if (strClass == "numeric-empty") {
          // cek apakah angka, kosong atau tidak
          if (isNaN(el.value) && el.value != "") {
            alert("Error numeric data!!!");
            el.focus();
            return false;
          }
        } else if (strClass == "string") {
          // cek kosong atau tidak
          if (el.value == "") {
            alert("Error string data! {empty}");
            el.focus();
            return false;
          }
        } else if (strClass == "string-empty") {
          // nothing
        } else if (strClass == "date") {
          if (!validDate(el.value)) {
            alert("Error date data!");
            el.focus();
            return false;
          }
        } else if (strClass == "date-empty") {
          if (el.value != "") {
            if (!validDate(el.value)) {
              alert("Error date data!");
              el.focus();
              return false;
            }
          }
        } else if (strClass == "time") {
          if (!validTime(el.value)) {
            alert("Error time data!");
            el.focus();
            return false;
          }
        } else if (strClass == "time-empty") {
          if (el.value != "") {
            if (!validTime(el.value)) {
              alert("Error time data!");
              el.focus();
              return false;
            }
          }
        } else {
          // nothing
        }

      }
    }
  }

  //alert("OK" + x);
  return true;
} // validateForm

// fungsi untuk melakukan validasi jam, format HH:MM, atau HH:MM:SS
function validTime(strTime)
{
  if (strTime == "") {
    return false;
  }
  //strTime = strTime.trim();
  arrTime = strTime.split(":");
  if (arrTime.length == 3) {
    if (isNaN(arrTime[0]) || isNaN(arrTime[1]) || isNaN(arrTime[2])) {
      return false;
    } else {
      jam = parseFloat(arrTime[0]);
      menit = parseFloat(arrTime[1]);
      detik = parseFloat(arrTime[2]);

      if (isNaN(jam) || isNaN(menit) || isNaN(detik)) return false;
      if (jam < 0 || jam > 23) return false;
      if (menit < 0 || menit > 59) return false;
      if (detik < 0 || detik > 59) return false;

    }
  } else if (arrTime.length == 2) {
    if (isNaN(arrTime[0]) || isNaN(arrTime[1])) {
      return false;
    } else {
      jam = parseFloat(arrTime[0]);
      menit = parseFloat(arrTime[1]);

      if (isNaN(jam) || isNaN(menit)) return false;
      if (jam < 0 || jam > 23) return false;
      if (menit < 0 || menit > 59) return false;

    }
  } else {
    return false;
  }

  return true;
}//validTime

// fungsi untuk validasi tanggal
// format yang disepakati = YYYY-MM-DD
function validDate(strDate)
{

  if (strDate == "") {
    return false;
  }

  arrDate = strDate.split("-");
  if (arrDate.length == 3) {
    if (isNaN(arrDate[0]) || isNaN(arrDate[1]) || isNaN(arrDate[2])) {
      return false;
    } else {
      tahun = parseFloat(arrDate[0]);
      bulan = parseFloat(arrDate[1]);
      tanggal = parseFloat(arrDate[2]);

      if (isNaN(tahun) || isNaN(bulan) || isNaN(tanggal)) return false;

      if (tahun < 0) return false;
      if (bulan < 1 || bulan > 12) return false;
      if (tanggal < 1 || tanggal > 31) return false;

      // validasi tanggalnya
      if (bulan == 4 || bulan == 6 || bulan == 9 || bulan == 11) {
        if (tanggal > 30) return false;
      } else if (bulan == 2) {
        if ((tahun % 4) == 0) { // kabisat
          if (tanggal > 29) return false;
        } else {
          if (tanggal > 28) return false;
        }
      }
    }
  } else {
    return false;
  }

  return true;
}//validDate

// fungsi untuk menstandardkan format angka pecahan, jad cuma 2 angka dibelakagn koma
function standardFormat(fltNum)
{
  if (isNaN(fltNum)) return fltNum;
  if (fltNum == "") return 0;
  strNum = "" + fltNum;
  arr = strNum.split('.');
  if (arr.length <= 1) return fltNum;

  dec = arr[1].substr(0,3);
  if (dec.length == 3)
  {
    dec = Math.round(dec/10);
  }
  dec = parseFloat(dec);
  if (dec == 0) strRes = arr[0];
  else strRes = arr[0] + "." + dec;
  return parseFloat(strRes);
}//standardFormat

// fungsi untuk membandingkan jam dengan di javascript
// format input: YYYY-MM-DD
// output: -1 jika dt1 < dt2, 0 jika sama, 1 jika dt1 > dt2
function dateCompare(dt1, dt2)
{

  bol1 = validDate(dt1);
  bol2 = validDate(dt2);

  if (!bol1 && !bol2) return 0;
  else if (!bol1) return -1;
  else if (!bol2) return 1;
  else if (dt1 == dt2) return 0;

  // data valid
  result = 0; //default

  // sekedar chek apakah ada data jam dibelakangnya
  arr1 = dt1.split(" ");
  arr2 = dt2.split(" ");

  dt1 = arr1[0];
  dt2 = arr2[0];

  // split data
  arr1 = dt1.split("-");
  arr2 = dt2.split("-");

  strThn1 = parseFloat(arr1[0]);
  strThn2 = parseFloat(arr2[0]);
  strBln1 = parseFloat(arr1[1]);
  strBln2 = parseFloat(arr2[1]);
  strTgl1 = parseFloat(arr1[2]);
  strTgl2 = parseFloat(arr2[2]);

  if (strThn1 == strThn2) // cek tahun
  {
    if (strBln1 == strBln2) // cek bulan
    {
      if (strTgl1 == strTgl2) // cek tanggal
      {
        result = 0;
      }
      else
        result = (strTgl1 < strTgl2) ? -1 : 1;
    }
    else
      result = (strBln1 < strBln2) ? -1 : 1;
  }
  else
    result = (strThn1 < strThn2) ? -1 : 1;

  return result;
}//dateCompare

/*
Fungsi terbilang dalam JavaScript
dibuat oleh Budi Adiono (iKode.net)
*/
function dateDiff(date1, date2)
{
  if (!validDate(date1) || !validDate(date2)) return 0;

  date1 = date1.split(strDateDelimiter);
  date2 = date2.split(strDateDelimiter);
  sDate = new Date(date1[0]+","+date1[1]+","+date1[2]);
  eDate = new Date(date2[0]+","+date2[1]+","+date2[2]);
  daysApart = (Math.round((sDate-eDate)/86400000));
  return daysApart;
}

function terbilang(bilangan) {

  bilangan    = String(bilangan);
  var angka   = new Array('0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0');
  var kata    = new Array('','Satu','Dua','Tiga','Empat','Lima','Enam','Tujuh','Delapan','Sembilan');
  var tingkat = new Array('','Ribu','Juta','Milyar','Triliun');

  var panjang_bilangan = bilangan.length;

  /* pengujian panjang bilangan */
  if (panjang_bilangan > 15) {
    kaLimat = "Diluar Batas";
    return kaLimat;
  }

  /* mengambil angka-angka yang ada dalam bilangan, dimasukkan ke dalam array */
  for (i = 1; i <= panjang_bilangan; i++) {
    angka[i] = bilangan.substr(-(i),1);
  }

  i = 1;
  j = 0;
  kaLimat = "";


  /* mulai proses iterasi terhadap array angka */
  while (i <= panjang_bilangan) {

    subkaLimat = "";
    kata1 = "";
    kata2 = "";
    kata3 = "";

    /* untuk Ratusan */
    if (angka[i+2] != "0") {
      if (angka[i+2] == "1") {
        kata1 = "Seratus";
      } else {
        kata1 = kata[angka[i+2]] + " Ratus";
      }
    }

    /* untuk Puluhan atau Belasan */
    if (angka[i+1] != "0") {
      if (angka[i+1] == "1") {
        if (angka[i] == "0") {
          kata2 = "Sepuluh";
        } else if (angka[i] == "1") {
          kata2 = "Sebelas";
        } else {
          kata2 = kata[angka[i]] + " Belas";
        }
      } else {
        kata2 = kata[angka[i+1]] + " Puluh";
      }
    }

    /* untuk Satuan */
    if (angka[i] != "0") {
      if (angka[i+1] != "1") {
        kata3 = kata[angka[i]];
      }
    }

    /* pengujian angka apakah tidak nol semua, lalu ditambahkan tingkat */
    if ((angka[i] != "0") || (angka[i+1] != "0") || (angka[i+2] != "0")) {
      subkaLimat = kata1+" "+kata2+" "+kata3+" "+tingkat[j]+" ";
    }

    /* gabungkan variabe sub kaLimat (untuk Satu blok 3 angka) ke variabel kaLimat */
    kaLimat = subkaLimat + kaLimat;
    i = i + 3;
    j = j + 1;

  }

  /* mengganti Satu Ribu jadi Seribu jika diperlukan */
  if ((angka[5] == "0") && (angka[6] == "0")) {
    kaLimat = kaLimat.replace("Satu Ribu","Seribu");
  }

  return kaLimat + "Rupiah";
} // terbilang
