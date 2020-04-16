
//some utilities we will eventually break out into seprate stuff
Date.prototype.resetHandlers = [];

Date.prototype.addDays = function(num_days)
{
  return new Date(this.getTime() + (num_days *86400000));
}

Date.prototype.addMinutes = function(minutes)
{
  return new Date(this.getTime() + (minutes *60000));
}

Date.prototype.addHours = function(hours)
{
  return new Date(this.getTime() + (hours *3600000));
}

Date.prototype.midnight = function()
{
  return new Date(this.getFullYear(), this.getMonth(), this.getDate());
}
Date.prototype.isSunday = function() { return this.getDay() == 0; }

Date.prototype.sundayBefore = function()
{
  var wkdy = this.getDay();
  if (wkdy == 0) //return the date minus 7 days
  {
    return this.addDays(-7);
  }
  else 
  {
    return this.addDays(-1*wkdy);
  }
}

Date.prototype.sundayNext = function()
{
  var wkdy = this.getDay();
  if (wkdy == 0) //return the date minus 7 days
  {
    return this.addDays(7);
  }
  else 
  {
    return this.addDays(7 - wkdy);
  }
}

Date.prototype.reset = function()
{
  this.setMonth(0);
  this.setDate(1);
  this.setFullYear(1970);
  this.notifyReset();
}

Date.prototype.notifyReset = function()
{
  this.resetHandlers.forEach(
    function(handler)
    {
      handler();
    }
  );
}

Date.prototype.onReset = function(handler)
{
  this.resetHandlers.push(handler);
}

Date.fromString = function(s)
{
  if (s instanceof Date)
      return s;

  var d = [];
  var t = [];

  if (s.indexOf(' ') == -1)
  {
    d = s.split('-');
    t = ['0', '0', '0'];
  }
  else 
  {
    var p = s.split(' ');
    d = p[0].split('-');
    t = p[1].split(':');
  }
  return new Date(
    parseInt(d[0]), parseInt(d[1])-1, parseInt(d[2]), 
    parseInt(t[0]), parseInt(t[1]), parseInt(t[2])
  );
}

Date.prototype.toFriendlyTime = function()
{
  var hr = this.getHours();
  var ampm = "AM";
  if (hr > 12)
  {
    hr = hr - 12;
    ampm = "PM";
  }
  var min = (this.getMinutes()).toString();
  if (min.length < 2)
    min = '0'+min;
 
  return hr+":"+min+" "+ampm;
}

Date.prototype.toString = function(include_time = true)
{
  return this.toServerString(include_time);
}

Date.prototype.toServerString = function(include_time = true)
{
  var year = this.getFullYear();
  var month = (this.getMonth() + 1).toString();
  var day = (this.getDate()).toString();

  if (month.length < 2)
    month = '0'+month;
  if (day.length < 2)
    day = '0'+day;

  if (!include_time)
    return year+'-'+month+'-'+day;

  var hr = this.getHours().toString();
  var min = (this.getMinutes()).toString();

   if (hr.length < 2)
    hr = '0'+hr;
  if (min.length < 2)
    min = '0'+min;

  return year+'-'+month+'-'+day+' '+hr+':'+min+':00';
  
}


Date.prototype.prettyDateTime = function()
{
  return this.prettyDate()+' at '+this.prettyTime();
}

Date.prototype.dayName = function() { return moment(this).format('Do'); }
Date.prototype.weekdayName = function() { return moment(this).format('dddd'); }
Date.prototype.monthName = function() { return moment(this).format('MMMM'); }

Date.prototype.prettyDate = function() { return this.weekdayName()+' '+this.monthName()+' '+this.dayName()+', '+this.getFullYear(); }
Date.prototype.prettyTime = function() { return moment(this).format('h:mm A'); }
