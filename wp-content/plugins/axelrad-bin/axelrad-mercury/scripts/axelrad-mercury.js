/*
requires 
tinycolor.js
moment.js

*/

class Mercury 
{
  constructor(log_prefix) 
  { 
    this.log_prefix = log_prefix;
  }
  
  log(stuff)
  {
    window.logger.log(this.log_prefix+": "+stuff);
  }
  
}

class MercuryHasEvents extends Mercury
{
  constructor(log_prefix)
  {
    super(log_prefix);
    this._bindings = [];
  }
  
  isBound(event)
  {
    return this._bindings[event] != null;
  }
  
  bind(event, handler)
  {
    if (this._bindings[event] == null)
      this._bindings[event] = [];
    
    this.log('binding '+event+' to '+handler);
    this._bindings[event].push(handler);
  }
  
  fire(event, data)
  {
    if (this._bindings[event] == null)
      return;
    
    this.log('firing '+event);
    var bindings = this._bindings[event];
    var value = null;
    bindings.forEach(
      function(handler)
      {
        var val = handler(data);
        if (u.isDefined(val))
          value = val;
      }
    );
    
    return value;
  }
}

class MercuryModelProp extends MercuryHasEvents
{
  constructor(parent, name, initial_value = null)
  {
    super(name);
    
    this._name = name;
    this.log_prefix = '[ModelProp '+name+']';
    this._parent = parent;
    this._value = initial_value;
    this._error = null;
  
    this._runningOperation = false;
    this._deleting = false;
    this._editing = false;
    
    this._silent = false;
    this.log(name);
    
    this._model = null;
  }

  get model()
  {
    if (this._parent == null) return this;
    
    if (this._model == null)
    {
      var p = this._parent;
      while (p != null)
      {
        this._model = p;
        p = p._parent;
      }
    }
    
    return this._model;
  }
  
  
  isEmpty()
  {
    return this.value == null || Object.entries(this.value).length == 0;
  }
  
  runOperation(name, httpMethod, data)
  {
    if (!u.isDefined(data) || !u.isDefined(httpMethod))
      alert("runOperation requires data and httpMethod");
    
    this._runningOperation = true;
    if (!this._doValidate(data)) return;
    
    var me = this;
    
    if (httpMethod.toLowerCase() == 'post')
    {
      this.post(name, data, 
        function(response)
        {
          if (response.success)
          {
            me.fire('completed-operation', name);
            me._runningOperation = false;
          }
        }
      );
    }
    else
    {
      this.get(name, data, 
        function(response)
        {
          if (response.success)
          {
            me.fire('completed-operation', name);
            me._runningOperation = false;
          }
        }
      );
    }
    
  }
  
  
  create(data)
  {
    if (!u.isDefined(data))
      alert("create requires data to be passed as the prospective properties");
    
    this._creating = true;
    if (!this._doValidate(data)) return;
    
    var me = this;
    
    this.post('create', data, 
      function(response)
      {
        if (response.success)
        {
          me.value = response.data;
          me.fire('created');
          me._creating = false;
        }
      }
    );
  }
  
  update(data)
  {
    if (!u.isDefined(data))
      alert("update requires data to be passed as the prospective properties");
    
    this._updating = true;
    if (!this._doValidate(data)) return;
    
    var me = this;
    
    
    this.post('update', data, 
      function(response)
      {
        if (response.success)
        {
          me.value = response.data;
          me.fire('updated');
          me._updating = false;
        }
      }
    );
  }
  
  fetch(data = null)
  {
    var me = this;
    
    this.get('fetch', data, 
      function(response)
      {
        if (response.success)
        {
          me.value = response.data;
          me.fire('fetched');
        }
      }
    );
  }
  
  delete()
  {
    var me = this;
    
    this.beginDelete();
    
    this.get('delete', this.value, 
      function(response)
      {
        if (response.success)
        {
          me.fire('deleted');
        }
      }
    );
  }
  
  
  get(cmdName, data, responseHandler)
  {
    var url = '/wp-json/mercury/v1/get';
    var p = this._getParams(cmdName, data);
    this.log('get '+url+' '+JSON.stringify(p));
    
    var me = this;
    var send_args = {'command': cmdName, 'prop' : me.name };
    me.model.fire('sending', send_args);
    
    mercury.get(url, p).done(
      function(response)
      {
        me.model.fire('send-complete', send_args);
        me.log('get.done '+JSON.stringify(response));
        if (!response.success)
          me.setError(response.data.code, response.data.message);

        responseHandler(response);
      }
    ).fail(
      function(response)
      {
        me.model.fire('send-complete', send_args);
        me.log('get.fail '+JSON.stringify(response));
        var error = this.readError(response);
        me.setError(error.code, error.message);
        responseHandler({'success' : false, 'code' : error.code, 'message' : error.message });
      }
    );
  }
  
  readError(response)
  {
    if (response.success)
    {
      return response;
    }
    else
    {
      if (u.isDefined(response.message))
      {
        return { 'code': response.code, 'message' : response.message};
      }
      else
      {
        var code = response.status;
        var statusText = response.statusText;
        if (response.statusText == 'parsererror')
        {
          code = 500;
          statusText = 'The server code was not understood.';
        }
        return { 'code': code, 'message' : statusText };
      }
    }
  }
  
  post(cmdName, data, responseHandler)
  {
    var url = '/wp-json/mercury/v1/post';
    var p = this._getParams(cmdName, data);
    this.log('post '+url+' '+JSON.stringify(p));
    
    var me = this;
    var send_args = {'command': cmdName, 'prop' : me.name };
    me.model.fire('sending', send_args);
    
    mercury.post(url, p).done(
      function(response)
      {
        me.model.fire('send-complete', send_args);
        me.log('post.done '+JSON.stringify(response));
        if (!response.success)
          me.setError(response.data.code, response.data.message);

        responseHandler(response);
      }
    ).fail(
      function(response)
      {
        me.model.fire('send-complete', send_args);
        me.log('post.fail '+JSON.stringify(response));
        var error = this.readError(response);
        me.setError(error.code, error.message);
        responseHandler({'success' : false, 'code' : error.code, 'message' : error.message });
      }
    );
  }
  
  _getParams(cmd_name, data = null)
  {
    return {
      'namespace' : mercury.namespace, 
      'model_name' : mercury.model.name, 
      'prop_name' : this instanceof MercuryModel ? '' : this.name, 
      'cmd_name' : cmd_name, 
      'data' : JSON.stringify(data == null ? this.value : data) 
    };
  }
  
  _handleResponse(response, event_name)
  {
    this.log(JSON.stringify(response));
    
    if (!response.success)
      this.setError(response.data.code, response.data.message);
    else
    {
      this.clear();
      this.value = response.data;
      if (event_name != 'changed')
      {
        this.fire(event_name);
      }  
    }
  }
  
  copyFrom(modelProp)
  {
    this.value = JSON.parse(JSON.stringify(modelProp.value));
  }
  
  copyTo(modelProp)
  {
    modelProp.value = JSON.parse(JSON.stringify(this.value));
  }
  
  get isDeleting() { return this._deleting; }
  get isUpdating() { return this._editing; }
  get isCreating() { return this._creating; }  
  
  beginOperation(name) { this.fire('begin-operation', name); }
  cancelOperation(name) { this.fire('cancel-operation', name); }
  
  
  beginCreate() { this._creating = true; this.fire('creating'); }
  cancelCreate() { this._creating = false; this.fire('cancel-create'); }
  
  beginUpdate() { this._editing = true; this.fire('updating'); }
  cancelUpdate() { this._editing = false; this.fire('cancel-update'); }
  
  beginDelete() { this._deleting = true; this.fire('deleting'); }
  cancelDelete() { this._deleting = false; this.fire('cancel-delete'); }
  
  creating(handler) { this.bind('creating', handler); }
  createCancelled(handler) { this.bind('cancel-create', handler); }
  
  
  deleting(handler) { this.bind('deleting', handler); }
  deleteCancelled(handler) { this.bind('cancel-delete', handler); }
  
  updating(handler) { this.bind('updating', handler); }
  updateCancelled(handler) { this.bind('cancel-update', handler); }
  
  operationStart(handler) { this.bind('begin-operation', handler); }
  runningOperation(handler) { this.bind('running-operation', handler); }
  operationCancelled(handler) { this.bind('cancel-operation', handler); }
  
  
  deleted(handler) { this.bind('deleted', handler); }
  changed(handler) { this.bind('changed', handler); }
  fetched(handler) { this.bind('fetched', handler); }
  created(handler) { this.bind('created', handler); }
  updated(handler) { this.bind('updated', handler); }
  operationComplete(handler) { this.bind('completed-operation', handler); }
  
  //this event is fired before create and update and passes an argument that allows
  // cancelling of the operation along with the data
  validate(handler) { this.bind('validate', handler); }
  
  _doValidate(data)
  {
    var args = {'cancel' : false, 'data' : data};
    this.fire('validate', args);
    return !args.cancel;
  }
  
  get name() { return this._name; }
  get parent() { return this._parent; }
  
  get error() { return this._error; }
  
  setError(code, message)
  {
    this._error = {'code' : code, 'message' : message};
    this.fire('on-error');
  }
  
  onError(handler)
  {
    this.bind('on-error', handler);
  }
  
  
  get value() { return (typeof this._value !== 'undefined' ? this._value : null); }
  
  set value(val)
  {
    if (val != this._value)
    {
      this.log(this._name+'.value set to '+val);
      this._value = val;
      this.fire('changed', val);
    }
  }
  
  clear() { this.value = null; }
}

class MercuryModelList extends MercuryModelProp
{
  constructor(parent, name, key_prop_name, initial_value = null)
  {
    super(parent, name, initial_value);
    this.key_prop_name = key_prop_name;
    this.selectedKeys = [];
    
    this._pagenum = 1;
    this._pagesize = 0;
    this._pagecount = 0;
    this._totalrows = 0;
    
    var me = this;
    this.changed(
      function()
      {
        me.log('clearing the selection for '+me.name);
        me.clearSelection();
      }
    );
  }
  
  get pageSize() { return this._pagesize; }
  set pageSize(size) 
  { 
    if (this._pagesize == size) return;
    this._pagesize = size; 
    this.fire('page-size-changed');
  }
  
  get pageNum() { return this._pagenum; }
  set pageNum(num) 
  { 
    if (this._pagenum == num) return;
    this._pagenum = num; 
    this.fire('page-num-changed');
  }
  
  get pageCount() { return this._pagecount; }
  get totalRows() { return this._totalrows; }
  
  
  onPageSizeChanged(handler) { this.bind('page-size-changed', handler); }
  onPageCountChanged(handler) { this.bind('page-count-changed', handler); }
  onPageNumChanged(handler) { this.bind('page-num-changed', handler); }
  
  fetchPage(data = null)
  {
    var me = this;
    
    //in the _getParams override we'll set some additional params.
    this.get('fetch_page', data, 
      function(response)
      {
        if (response.success)
        {
          me.value = response.data;
          var pageCount = parseInt(response.total_count / response.page_size);
          if (response.total_count % response.page_size > 0) pageCount += 1;
          
          me._totalrows = response.total_count;
          if (me._pagecount != pageCount)
          {
            me._pagecount = pageCount;
            me.fire('page-count-changed');
          }
          
          me.fire('fetched');
        }
      }
    );
  }
  
  _getParams(cmd_name, data = null)
  {
    var p = super._getParams(cmd_name, data);
    
    p.is_paged = this.pageSize > 0 ? 'true' : 'false';
    if (this.pageSize > 0)
    {
      p.page_num = this.pageNum;
      p.page_size = this.pageSize;
    }
    return p;
  }
  
  _handleResponse(response, event_name)
  {
    if (response.success && this.pageSize > 0)
    {
      this.pageCount = response.page_count;
    }
    super._handleResponse(response, event_name);
  }
  
  get items() { return this.value; }
  get length() { return this.value.length; }
  
  get selectedRow()
  {
    if (this.selectedKeys.length < 0 ||
      this.selectedKeys.length > 1)
      return null;
    
    return this.selectedRows[0];
  }

  clear()
  {
    this.value = [];
  }
  
  
  selectRow(key)
  {
    if (this._setSelectItem(key, true))
      this.fire('selection-changed');
  }
  
  selectRows(keys)
  {
    this.selectedKeys = [];
    for (var i in keys)
    {
      this._setSelectItem(keys[i], true);
    }
    this.fire('selection-changed');
  }
  
  clearSelection()
  {
    this.selectedKeys = [];
    this.fire('selection-changed');
  }
  
  unSelectRow(key)
  {
    if (this._setSelectItem(key, false))
      this.fire('selection-changed');
  }
  
  unSelectRows(keys)
  {
    for (var i in keys)
    {
      this._setSelectItem(keys[i], false);
    }
    this.fire('selection-changed');
  }
  
  _setSelectItem(key, selected)
  {
    var theIndex = this.selectedKeys.indexOf(key);
    
    if (selected && theIndex == -1) //the key is NOT in the array and we are adding it
    {
      this.selectedKeys.push(key);
      return true;
    }
    
    if (!selected && theIndex > -1) //the key Is in the array and we're removing it
    {
      this.selectedKeys.splice(theIndex, 1);
      return true;
    }
    return false;
  }
  
  rowIsSelected(index)
  {
    if (index > this.value.length - 1)
      return false;
    
    return this.selectedKeys.indexOf(
      this.value[index][this.key_prop_name]) > -1;
  }
  
  get selectedCount() { return this.selectedKeys.length; }
  
  get selectedRows()
  {
    this.log('scanning for '+this.selectedKeys.length+' selected keys');
    
    var rows = [];
    for (var i = 0; i < this.selectedKeys.length; i++)
    {
      var key = this.selectedKeys[i];
      var row = this.findRow(key);
      this.log('adding row '+JSON.stringify(row));
      rows.push(row);
    }
    
    return rows;
  }
  
  findRow(val, prop = '')
  {
    if (prop == '') prop = this.key_prop_name;
    
    for (var i = 0; i < this.value.length; i++)
    {
      if (this.value[i][prop] == val)
        return this.value[i];
    }
    return null;
  }
  
  getIndex(key)
  {
    for (var i = 0; i < this.value.length; i++)
    {
      if (this.value[i][this.key_prop_name] == key)
        return i;
    }
    
    return -1;
  }
  
  addRow(data)
  {
    this.value.push(data);
    this.fire('row-added', data);
  }
  
  updateRow(key, data)
  {
    var index = this.getIndex(key);
    if (index > -1)
    {
      this.value[index] = data;
      this.fire('row-changed', data);
    }
  }
  
  deleteRow(key)
  {
    var index = this.getIndex(key);
    if (index > -1)
    {
      var row = this.value.splice(index, 1);
      this.fire('row-deleted', row);
    }
  }
  
  onRowAdded(handler)
  {
    this.bind('row-added', handler);
  }
  onRowChanged(handler)
  {
   this.bind('row-changed', handler); 
  }
  
  onRowDeleted(handler)
  {
   this.bind('row-deleted', handler); 
  }
  
  onSelectionChanged(handler)
  {
    this.bind('selection-changed', handler);
  }
}

class MercuryModel extends MercuryModelProp
{
  constructor(name)
  {
    super(null, name);
    this.baseUrl = ''; //set in the php
  }
  
  init()
  {
    this.onInit(this);
  }
  
  onInit(me)
  {
  }
        
  sending(handler) { this.bind('sending', handler); }
  doneSending(handler) { this.bind('send-complete', handler); }
  
  _formatUrl(resourceName, queryString)
  {
    return mercury._formatUrl(resourceName, queryString);
  }
  
  _flatten(data)
  {
    return mercury._flatten(data);
  }
}

class MercuryController extends Mercury
{
  //we'll use the php to set the id of the view...
  constructor(id)
  {
    super(id);
    this.id = id;
    this.view_id = '';
    this._view = null;
    this.log_prefix = '[Controller '+id+']';
    console.log('new controller ' +this.id);
  }
  
  get view() { return this._view; }
  
  init()
  {
    this.log('initializing '+this.id+'... with view id '+this.view_id);
    this._view = window[this.view_id];
    this.log(this.id+' initialized with view: '+this._view);
    this.onInit(this);
  }
  
  onInit(me)
  {
  }
}

class MercuryComponent extends MercuryHasEvents
{
  
  constructor(id)
  {
    super(id);
    
    this.id = id;
    this.parent = null;
    this.log_prefix = '[Component '+id+']';
    this.children = [];
  }
  
  init() 
  {
    this.log('init');
    this.onInit(this);
    
    var me = this;
    this.children.reverse().forEach(
      function(item)
      {
        me.log('calling: '+item.id+'.init');
        item.init();
      }
    );
    
    this.fire('initialized');
  }
  
  onInit(t = null) 
  {
    this.log('onInit');
  }
  
  initialized(handler)
  {
    this.bind('initialized', handler);
  }
  
  addChild(name, child)
  {
    this.log('adding child: '+name+' = '+child);
    this.children.push(child);
    this[name] = child;
  }
  
  select(query = '')
  {
    return jQuery(query ? query : '#'+this.id);
  }
  
  selectId(id)
  {
    if (id == this.id)
      return this.element();
    
    return this.select('#'+this.id+' #'+id);
  }
  
  element(sub_id = '')
  {
    if (sub_id) sub_id = '_'+sub_id;
    return this.select('#'+this.id+sub_id); 
  }
  
  show(bool = true, selector = '')
  {
    if (bool)
      this.select(selector).show();
    else 
      this.select(selector).hide();
  }
  
  attr(name, value = null)
  {
    if (value == null)
      return this.element().attr(name);
    else
      this.element().attr(name, value);
  }
}

class MercuryListComponent extends MercuryComponent
{
  constructor(id) 
  { 
    super(id); 
    this.itemTemplates = []; //for list components
    this.items = [];
    this._selectedKey = null;
    this._selectedKeys = [];
    
    this.listElementTag = '';
    
    this.log_prefix = '[ListComponent '+id+']';
  }
  
  onInit(me)
  {
    
     
    this.setItemClickHandler(me);
    
    if (u.isDefined(this.selectedItemClass) &&
      this.selectedItemClass.indexOf('{') == -1)
    {
      this.onSelectionChanged(
        function()
        {
          var rows = me.allItems();
          rows.each(
            function(i, row)
            {
              var el = jQuery(row);
              var key = jQuery(row).attr('data-key');
              me.log('row key = '+key);
              if (me.isSelected(key))
              {
                el.removeClass(me.itemClass);
                el.addClass(me.selectedItemClass);
              }
              else 
              {
                el.removeClass(me.selectedItemClass);
                el.addClass(me.itemClass);
              }
            }
          );
        }
      );
    }

  }
  
  setItemClickHandler(me)
  {
    me.select('#'+this.containerElementId).on('click', '[list-id="'+this.id+'"]',
      function(event)
      {
        me.log('detected a click on row with key '+jQuery(this).attr('data-key'));
        me.selectedKey = jQuery(this).attr('data-key');
        me.uncheckAll();
        me.fire('item-clicked');
      }
    );
    
    //multi select
    if (this.multiSelectInputClass)
    {
      me.select('#'+this.containerElementId).on('click', '.'+this.multiSelectInputClass, 
        function(event)
        {
          if (this.checked)
            me.selectRow(jQuery(this).attr('data-key'));
          else 
            me.unSelectRow(jQuery(this).attr('data-key'));

          //event.stopPropagation();
          me.syncCheckBoxes();
        }
      );
     
      if (this.selectAllInputId)
      {
        me.select('#'+this.selectAllInputId).on('click',  
          function(event)
          {
            var checked = this.checked;
            var boxes = me.select('#'+me.containerElementId+" input:checkbox");
            boxes.each(
              function(i, box)
              {
                var b = jQuery(box);
                if (checked)
                  me.selectRow(b.attr('data-key'));
                else 
                  me.unSelectRow(b.attr('data-key'));
              }
            );
            //event.stopPropagation();
            me.syncCheckBoxes();
          }
        );
      }
    }    
  }

  syncCheckBoxes()
  {
    if (!this.multiselect) return;
    
    var me = this;
    if (me.selectedKeys.length == 0)
    {
      this.uncheckAll();
      return;
    }
    else
      me.select('#'+this.selectAllInputId).prop('checked', 
              me.selectedKeys.length == me.items.length);

    var checkAll = me.select('#'+this.selectAllInputId).prop('checked');
    var boxes = me.select('#'+me.containerElementId+" input:checkbox");
    boxes.each(
      function(i, box)
      {
        var b = jQuery(box);
        b.prop('checked', checkAll || me.isSelected(b.attr('data-key')));
      }
    );
  }
  
  uncheckAll()
  {
    if (!this.multiselect) return;
    this.select('#'+this.id+" input:checkbox").prop('checked', false);
    this.select('#'+this.selectAllInputId).prop('checked', false);
  }
  
  get containerElementId() 
  {
    return this.id; 
  }
 
  get itemClass() { return this.element().attr('item-class'); }
  set itemClass(value) { this.element().attr('item-class', value); }
  
  get selectedItemClass() { return this.element().attr('selected-item-class'); }
  set selectedItemClass(val) { this.element().attr('selected-item-class', val); }
  
  get multiSelectInputClass() { return this.element().attr('multi-select-input-class'); }
  
  get selectAllInputId() { return this.element().attr('select-all-input-id'); }
  
  get multiselect() { return this.multiSelectInputClass != ''; }
    
  get keyProperty() { return this.element().attr('key-prop'); }
  
  get selectedKey() { return this._selectedKeys.length == 1 ? this._selectedKeys[0] : null; }

  set selectedKey(key)
  {
    //clear stuff out..
    if ((this._selectedKeys.length == 1 && this._selectedKeys[0] != key) ||
        this._selectedKeys.length == 0)
      {
        this.clearSelection();
        this.selectRow(key);
      }
  }

  get selectedItem()
  {
    if (this.multiselect)
    {
      if (this._selectedKeys.length == 1)
        return this.findItem(this._selectedKeys[0]);
      else
        return null;
    }
    else
    {
      this.log('get selectedItem');
      if (this.selectedKey == null) 
      {
        this.log('returning null');
        return null;
      }
      return this.findItem(this.selectedKey);
    }
  }
  
  get selectedKeys() { return this._selectedKeys; }
  
  clearSelection()
  {
    this._selectedKeys = [];
    this.fire('selection-changed');
  }
  
  toggleRow(key)
  {
    if (this.isSelected(key))
      this.selectRow(key);
    else
      this.unSelectRow(key);
  }
  
  //is additive in multiselect mode
  selectRow(key)
  {
    this.log('selectRow '+key);
    var index = this._selectedKeys.indexOf(key);
    this.log('index = '+index);
    if (!this.multiselect)
      this.clearSelection();
    
    if (index < 0)
    {
      this._selectedKeys.push(key);
      this.log('added '+key);
      this.fire('selection-changed');
    }
  }
  
  unSelectRow(key)
  {
    this.log('unSelectRow '+key);
    if (!this.multiselect)
    {
      if (this.selectedKey == key)
        this.clearSelection();
    }
    else
    {
      var index = this._selectedKeys.indexOf(key);
      this.log('index = '+index);
      if (index > -1)
      {
        this._selectedKeys.splice(index, 1);
        this.log('removed '+key);
        this.fire('selection-changed');
      }
    }
  }
  
  get selectedRows()
  {
    this.log('selectedRows '+JSON.stringify(this.selectedKeys));
    if (this.selectedKeys.length == 0) return [];
    
    var items = [];
    var me = this;
    this.selectedKeys.forEach(
      function(key)
      {
        items.push(me.findItem(key));
      }
    );
    return items;
  }
  
  isSelected(key)
  {
    if (this.multiselect)
    {
      var index = this.selectedKeys.indexOf(key);
      this.log('index of row with key '+key+' = '+index);
      return index != -1;
    }
    else
      return key == this.selectedKey;
  }
  
  onItemClicked(handler) { this.bind('item-clicked', handler); }

  onSelectionChanged(handler)
  {
    this.bind('selection-changed', handler);
  }
  
  addTemplate(name, html)
  {
    this.itemTemplates[name] = html;
  }

  onGetTemplateName(handler)
  {
    this.bind('get-item-template', handler);
  }
  
  onRenderingItem(handler)
  {
    this.bind('rendering-item', handler);
  }
  
  allItems()
  {
    return this.element().find('[list-id="'+this.id+'"]');
  }
  
  onPreRender(items) { }
  
  setItems(items)
  {
    this.items = items;
    var h = '';
    
    var opentag = '';
    var closetag = '';
    if (this.listElementTag)
    {
      opentag = '<'+this.listElementTag+'>';
      closetag = '</'+this.listElementTag+'>';
    }
    
    h+= this.getListOpenTag();
    
    for (var i in this.items)
    {
      h+= this.getRowOpenTag(i);
      var item = this.items[i];
      item.rowIndex = i; //allows templates to insert a row index if needed
      item.itemClass = this.itemClass; //allows templates to use token for item class if needed
      
      this.fire('rendering-item', item);
      
      var template_name = item.uses_template;
      if (!u.isDefined(template_name))
      {
        template_name = this.fire('get-item-template', item);
      }
      
      var template = this.itemTemplates[u.isDefined(template_name) ? template_name : 'default'];
      for (var p in item)
      {
        template = template.split('%'+p+'%').join(item[p]);
      }
      h+=opentag+template+closetag;
      h+= this.getRowCloseTag(i);
    }

    h+= this.getListCloseTag();
    this.selectId(this.containerElementId).html(h);
    this.fire('items-changed');
  }

  //allows for inserting columns and stuff
  getListOpenTag() { return ''; }
  getListCloseTag() { return ''; }
  
  getRowOpenTag(itemIndex) { return ''; }
  getRowCloseTag(itemIndex) { return ''; }
  
  onItemsChanged(handler) { this.bind('items-changed', handler); }
  
  findItem(val, prop = '')
  {
    if (prop == '') prop = this.keyProperty;
    
    this.log('finding item with '+prop+' = '+val);
    this.log('this.items.length = '+this.items.length);
    for (var i = 0; i < this.items.length; i++)
    {
      if (this.items[i][prop] == val)
        return this.items[i];
    }
    
    return null;
  }
  
  selectItem(key)
  {
    return jQuery('[list-id="'+this.id+'"][data-key="'+key+'"]');
  }
  keyExists(key)
  {
    for (var i = 0; i < this.items.length; i++)
    {
      if (this.items[i][this.keyProperty] == key)
        return true;
    }
    
    return false;
  }
  
  getRowKey(row)
  {
    return row[this.keyProperty];
  }
}

class MercuryView extends MercuryComponent
{
  constructor(id)
  {
    super(id);
    this.log_prefix = '[View '+id+']';
  }
}

class MercuryApp extends MercuryView
{
  constructor(id)
  {
    super(id);
    this.log_prefix = '[App '+id+']';
  }
  
  onInit(me)
  {
  }
}

class MercuryCore extends MercuryHasEvents
{
  constructor()
  {
    super('MercuryCore');
    
    this.app          = null;
    this.components   = [];
    this.views        = [];
    this.controllers  = [];
    this.model        = null;
    this.namespace    = '';
    this.baseUrl      = window.location.protocol+'//'+window.location.hostname;
    this.token        = '';
    this.token_param = '';
    this._testmode = false;
    this.tmOutput = null;
  }
  
  init()
  {
    
    console.log('mercury.init');
    
    var me = this;
    
    if (this.app == null) throw new Exception('No app node.');
    
    this.app.init();
    
    if (me.model != null) me.model.init();
    
    this.controllers.forEach(
      function(c)
      {
        me.log('calling init on '+c.id);
        c.model = me.model;
        c.init();
      }
    );
    
    
  }
  
  get testmode() { return this._testmode; }
  set testmode(val)
  {
    if (this._testmode != val)
    {
      this._testmode = val;
      this.fire('testmode');
    }
  }
  
  onTestModeChanged(handler) { this.bind('testmode', handler); }
  
  
  setTestModeOutput(component)
  {
    this.tmOutput = component;
  }
  
  output(stuff) { if (this.tmOutput != null) this.tmOutput.write(stuff); }
  
  //[prop-name] props
  setMappedProp(component_id, tag, element_id, prop_name)
  {
    var component = window[component_id];
    
    this.log('setting mapped property '+component_id+'.'+prop_name+' to value of '+element_id);
    
    if (!component.hasOwnProperty(prop_name))
    {
      var m = 'html';
      if (tag == 'input' || tag == 'select' || tag == 'textarea')
        m = 'val';
      
      Object.defineProperty(component, prop_name, 
        { 
          get : function() { return jQuery("#"+element_id)[m](); },
          set : function(val) { jQuery("#"+element_id)[m](val); }
        });
    }
  }
  
  addController(component_name, component_id)
  {
    var controllerName = component_name+'Controller';
    var controllerId = component_id+'_controller';
    try
    {
      var cls = eval(controllerName);
      this.log('typeof cls = '+(typeof cls));
      var controller = new cls(controllerId);
      window[controllerId] = controller;
      controller.view_id = component_id;
      this.controllers.push(controller);
      return controller;
    }
    catch (e)
    {
      this.log('could not load instance of controller '+controllerName+' '+e.message);
      return null;
    }
    
  }
  
  addComponent(typeName, componentName, componentId)
  {
    this.log('addComponent("'+typeName+'", "'+componentName+'", "'+componentId+'");');

    var component = null;
    try
    {
      var c = eval(componentName);
      component = new c(componentId);
      window[componentId] = component;
      
      if (typeName == 'component')
        this.components.push(component);
      else if (typeName == 'view')
        this.views.push(component);
      else if (typeName == 'app')
        this.app = component;
      else
        throw new Exception('Unknown component type '+typeName+'.');
      
      return component;
    }
    catch (e)
    {
      this.log('could not load instance of component '+componentName+' '+e.message);
      return null;
    }

  }

  
  addChild(parentId, childId)
  {
    this.log('app.addChild('+parentId+', '+childId+')');
    window[parentId].addChild(childId, window[childId]);
  }

  // addController(controller, view_id)
  // {
  //   eval('window["'+controller.id+'"] = controller;');
  //   this.controllers.push(controller);
  //   controller.view_id = view_id;
  //   return controller;
  // }
  
  // addComponent(type_name, component)
  // {
  //   this.log('addComponent("'+type_name+'", "'+component.id+'");');
  //   eval('window["'+component.id+'"] = component;');
    
  //   if (type_name == 'component')
  //     this.components.push(component);
  //   else if (type_name == 'view')
  //     this.views.push(component);
  //   else if (type_name == 'app')
  //     this.app = component;
  //   else
  //     throw new Exception('Unknown component type '+type_name+'.');
    
  //   return component;
  // }
  
  _formatUrl(resourceName, queryString = '')
  {
    var r = "";
    if (this.baseUrl.endsWith("/"))
    {
      if (!resourceName.startsWith("/"))
        r = this.baseUrl+resourceName;
      else 
        r = this.baseUrl+resourceName.substring(1);
    }
    else
    {
      if (!resourceName.startsWith("/"))
        r = this.baseUrl+"/"+resourceName;
      else 
        r = this.baseUrl+resourceName;
    }
    
    if (!u.isDefined(queryString))
      queryString = '';
    
    var q = '?'+queryString+(queryString ? '&' : '')+this.token_param+"="+encodeURIComponent(this.token)+"&mtx="+Date.now();
    r = r.endsWith("/") ? r : r+"/";
    return r+q;
    
  }
  
  send(method, resourceName, data)
  {
    if (method == 'GET')
      return this.get(resourceName, data);
    else if (method == 'POST')
      return this.post(resourceName, data);
    else
      throw 'Invalid http method for send.';
      
  }
  
  get(resourceName, queryString = '')
  {
    
    if (typeof queryString == 'object')
    {
      queryString = this._flatten(queryString);
    }
    
    var url = this._formatUrl(resourceName, queryString);
    this.log("mercury.get: "+url);
    
    if (this.testmode)
    {
      this.output(url+'&test_mode=1&debug=1');
      return null;
    }
    
    return jQuery.ajax(
       {
       url: url,
       method: "GET"
       }
     );
  }
  
  _flatten(data)
  {
    if (data == null)
      return '';
    
    var q = '';
    for (var key in data)
    {
      if (q != '') q+='&';
      q += key+'='+encodeURIComponent(data[key]);
    }
    
    return q;
  }
  
  post(resourceName, data)
  {
    var url = this._formatUrl(resourceName);
    this.log("mercury.post: "+url);
    this.log(JSON.stringify(data));
    
    if (this.testmode)
    {
      this.output(JSON.stringify(data));
      this.output(this._formatUrl(resourceName, 'test_mode=1&debug=1&data='+encodeURIComponent(JSON.stringify(data))));
      return null;
    }
    
    
    return jQuery.ajax(
       {
       url: url,
       method: "POST",
       data: data
       }
     );
  }
}

var mercury = new MercuryCore();

jQuery(
  function()
  {
    window.mercury.init();
  }
);


class MercuryUtils
{
  constructor() 
  {
    this.params = new URLSearchParams(window.location.search);
  }
  
  showLoading(message = '')
  {
    window.mercury_loading.show(message);
  }
  
  hideLoading()
  {
    window.mercury_loading.hide();
  }

  getTextColor(bg_color)
  {
    return tinycolor(bg_color).getBrightness() > 175 ? '#000': '#fff'; 
  }
  
  parseJson(val, defaultVal = null)
  {
    if (!this.isDefined(val))
      return defaultVal;
    else
      return JSON.parse(val);
  }
  
  isDefined(val) { return this.ex(val); }
  
  ex(val)
  {
    return (typeof val != "undefined" &&
        val != 0 &&
        val != '' &&
        val != null);        
  }
  
  escape(val)
  {
    if (!this.isDefined(val)) return val;
    
    return val.split("'").join("\'");
  }
  
  param(name)
  {
    if (this.params.has(name))
      return this.params.get(name);
    else
      return '';
  }
  
  get_url_date_value(date, include_time = false)
  {
    var year = date.getFullYear();
    var month = (date.getMonth() + 1).toString();
    var day = (date.getDate()).toString();
    
    if (month.length < 2)
      month = '0'+month;
    if (day.length < 2)
      day = '0'+day;
    
    if (!include_time)
      return year+'-'+month+'-'+day;
    
    var hr = date.getHours().toString();
    var min = (date.getMinutes()).toString();
    
     if (hr.length < 2)
      hr = '0'+hr;
    if (min.length < 2)
      min = '0'+min;
    
    return year+'-'+month+'-'+day+' '+hr+':'+min+':00';
  }
  
  sameDate(date1, date2)
  {
    return date1.getFullYear() == date2.getFullYear() &&
      date1.getMonth() == date2.getMonth() &&
      date1.getDate() == date2.getDate();
  }
  
  //assumes a date in yyyy-mm-dd hh:ii:ss format
  parseDate(s)
  {
    if (s instanceof Date)
      return s;
    
    var p = s.split(' ');
    var d = p[0].split('-');
    var t = p[1].split(':');
    
    return new Date(
      parseInt(d[0]), parseInt(d[1])-1, parseInt(d[2]), 
      parseInt(t[0]), parseInt(t[1]), parseInt(t[2]));
  }
  
  digitsOnly(value)
  {
    var v = value.split('');
    var r = '';
    for (var i in v)
    {
      if (!Number.isNaN(parseInt(v[i])))
        r+=v[i];
    }
    
    return r;
  }
  
  isValidEmail(value, regex = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/)
  {
    return value.match(regex) != null;
  }
  
  isValidPhone(value, regex = /^\d{10}$/)
  {
    return value.match(regex) != null;
  }
  
  setInputValid(id, isValid, message = '')
  {
    console.log('u.setInputValid '+id+' '+isValid);
    var input = jQuery("#"+id);
    var error_id = id+"_error_msg";
    if (isValid)
    {
      input.removeClass('input-invalid');
      jQuery("#"+error_id).remove();
    }
    else 
    {
      input.addClass('input-invalid');
      if (message)
        input.after('<div id="'+error_id+'" class="form-error-msg">'+message+'</div>');
    }
  }
  
  getInputValid(selector)
  {
    var input = jQuery(selector);
    return input.hasClass('input-invalid');
  }

}


class MercuryLogger
{
  constructor()
  {
  }
  
  log(stuff)
  {
    console.log(stuff);
  }
  
}

var logger = new MercuryLogger();
var u = new MercuryUtils();


//all attributes 
  
//   setAttrProp(component_id, prop_name, attr_name, attr_value)
//   {
//     var component = window[component_id];
//     this.log('setting attr property '+component_id+'.'+prop_name);
//     if (!Object.hasOwnProperty(component, prop_name))
//     {
//       Object.defineProperty(component, prop_name, 
//         { 
//           'get' : function() { return jQuery("#"+component_id).attr(attr_name); },
//           'set' : function(val) { jQuery("#"+component_id).attr(attr_name, val); }
//         });
      
//       //Object.entries(component)[prop_name] = attr_value;
//     }
    
//   }
  
  