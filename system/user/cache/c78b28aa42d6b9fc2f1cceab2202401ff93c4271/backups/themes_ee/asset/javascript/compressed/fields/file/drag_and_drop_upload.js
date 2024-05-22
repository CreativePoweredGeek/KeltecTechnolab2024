"use strict";function _typeof(e){return(_typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function _classCallCheck(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function _defineProperties(e,t){for(var r=0;r<t.length;r++){var n=t[r];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}function _createClass(e,t,r){return t&&_defineProperties(e.prototype,t),r&&_defineProperties(e,r),e}function _possibleConstructorReturn(e,t){return!t||"object"!==_typeof(t)&&"function"!=typeof t?_assertThisInitialized(e):t}function _getPrototypeOf(e){return(_getPrototypeOf=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function _inherits(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&_setPrototypeOf(e,t)}function _setPrototypeOf(e,t){return(_setPrototypeOf=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}function _assertThisInitialized(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}function _defineProperty(e,t,r){return t in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */
var DragAndDropUpload=function(e){function t(e){var r;_classCallCheck(this,t),r=_possibleConstructorReturn(this,_getPrototypeOf(t).call(this,e)),_defineProperty(_assertThisInitialized(_assertThisInitialized(r)),"handleDroppedFiles",function(e){r.setState({pendingFiles:null});var t=Array.from(e);if(t=t.filter(function(e){return""!=e.type}),!r.props.multiFile&&t.length>1)return r.setState({error:EE.lang.file_dnd_single_file_allowed});if(r.props.shouldAcceptFiles&&"string"==typeof r.props.shouldAcceptFiles(t)){var n=r.props.shouldAcceptFiles(t);if("string"==typeof n)return r.setState({error:n})}t=t.map(function(e){return e.progress=0,"image"!=r.props.contentType||e.type.match(/^image\//)||(e.error=EE.lang.file_dnd_images_only),e}),r.setState({files:r.state.files.concat(t)}),t=t.filter(function(e){return!e.error}),r.queue.enqueue(t,function(e){return r.makeUploadPromise(e)})}),_defineProperty(_assertThisInitialized(_assertThisInitialized(r)),"setDirectory",function(e){r.setState({directory:e||"all"})}),_defineProperty(_assertThisInitialized(_assertThisInitialized(r)),"chooseExisting",function(e){var t=r.props.filebrowserEndpoint.replace("=all","="+e);r.presentFilepicker(t,!1)}),_defineProperty(_assertThisInitialized(_assertThisInitialized(r)),"uploadNew",function(e){var t=r.props.uploadEndpoint+"&directory="+e;r.presentFilepicker(t,!0)}),_defineProperty(_assertThisInitialized(_assertThisInitialized(r)),"assignDropZoneRef",function(e){r.dropZone=e,r.props.assignDropZoneRef&&r.props.assignDropZoneRef(e)}),_defineProperty(_assertThisInitialized(_assertThisInitialized(r)),"removeFile",function(e){var t=r.state.files.findIndex(function(t){return t.name==e.name});r.state.files.splice(t,1),r.setState({files:r.state.files})});var n=r.getDirectoryName(e.allowedDirectory);return r.state={files:[],directory:n?e.allowedDirectory:"all",directoryName:n,pendingFiles:null,error:null},r.queue=new ConcurrencyQueue({concurrency:r.props.concurrency}),r}return _inherits(t,e),_createClass(t,[{key:"componentDidMount",value:function(){this.bindDragAndDropEvents()}},{key:"componentDidUpdate",value:function(e,t){this.toggleErrorState(!1),this.state.directory!=t.directory&&this.state.pendingFiles&&this.handleDroppedFiles(this.state.pendingFiles),this.state.error&&!t.error&&this.showErrorWithInvalidState(this.state.error),!this.state.error&&t.error&&this.toggleErrorState(!1),t.error&&this.setState({error:null})}},{key:"getDirectoryName",value:function(e){return"all"==e?null:(e=EE.dragAndDrop.uploadDesinations.find(function(t){return t.value==e}),e?e.label:e)}},{key:"bindDragAndDropEvents",value:function(){function e(e){e.preventDefault(),e.stopPropagation()}var t=this;["dragenter","dragover","dragleave","drop"].forEach(function(r){t.dropZone.addEventListener(r,e,!1)}),this.dropZone.addEventListener("drop",function(e){var r=e.dataTransfer.files;return"all"==t.state.directory?t.setState({pendingFiles:r}):void t.handleDroppedFiles(r)});var r=function(e){t.dropZone.classList.add("field-file-upload--drop")},n=function(e){t.dropZone.classList.remove("field-file-upload--drop")};["dragenter","dragover"].forEach(function(e){t.dropZone.addEventListener(e,r,!1)}),["dragleave","drop"].forEach(function(e){t.dropZone.addEventListener(e,n,!1)})}},{key:"makeUploadPromise",value:function(e){var t=this;return new Promise(function(r,n){var i=new FormData;i.append("directory",t.state.directory),i.append("file",e),i.append("csrf_token",EE.CSRF_TOKEN);var o=new XMLHttpRequest;o.open("POST",EE.dragAndDrop.endpoint,!0),o.upload.addEventListener("progress",function(r){e.progress=100*r.loaded/r.total||100,t.setState({files:t.state.files})}),o.addEventListener("readystatechange",function(){if(4==o.readyState&&200==o.status){var i=JSON.parse(o.responseText);switch(i.status){case"success":t.removeFile(e),t.props.onFileUploadSuccess(JSON.parse(o.responseText)),r(e);break;case"duplicate":e.duplicate=!0,e.fileId=i.fileId,e.originalFileName=i.originalFileName,n(e);break;case"error":e.error=t.stripTags(i.error),n(e);break;default:e.error=EE.lang.file_dnd_unexpected_error,console.error(o),n(e)}}else 4==o.readyState&&200!=o.status&&(e.error=EE.lang.file_dnd_unexpected_error,console.error(o),n(e));t.setState({files:t.state.files})}),i.append("file",e),o.send(i)})}},{key:"stripTags",value:function(e){var t=document.createElement("div");return t.innerHTML=e,t.textContent||t.innerText||""}},{key:"presentFilepicker",value:function(e,t){var r=this,n=$("<a/>",{href:e,rel:"modal-file"}).FilePicker({iframe:t,callback:function(e,t){return r.props.onFileUploadSuccess(e)}});n.click()}},{key:"warningsExist",value:function(){var e=this.state.files.find(function(e){return e.error||e.duplicate});return null!=e||this.state.pendingFiles}},{key:"resolveConflict",value:function(e,t){this.removeFile(e),this.props.onFileUploadSuccess(t)}},{key:"showErrorWithInvalidState",value:function(e){this.toggleErrorState(!0);var t=$(this.dropZone).closest(".field-control").find("> em");0==t.size()&&(t=$("<em/>")),$(this.dropZone).closest(".field-control").append(t.text(e))}},{key:"toggleErrorState",value:function(e){$(this.dropZone).closest("fieldset, .fieldset-faux").toggleClass("fieldset-invalid",e),e||$(this.dropZone).closest(".field-control").find("> em").remove()}},{key:"render",value:function(){var e=this,t=this.props.multiFile?EE.lang.file_dnd_drop_files:EE.lang.file_dnd_drop_file,r="all"==this.state.directory?EE.lang.file_dnd_choose_directory:EE.lang.file_dnd_uploading_to.replace("%s",this.getDirectoryName(this.state.directory));return this.state.pendingFiles&&(t=EE.lang.file_dnd_choose_file_directory,r=EE.lang.file_dnd_choose_directory_before_uploading),React.createElement(React.Fragment,null,React.createElement("div",{className:"field-file-upload"+(this.props.marginTop?" mt":"")+(this.warningsExist()?" field-file-upload---warning":"")+(this.state.error?" field-file-upload---invalid":""),ref:function(t){return e.assignDropZoneRef(t)}},this.state.files.length>0&&React.createElement(FileUploadProgressTable,{files:this.state.files,onFileErrorDismiss:function(t,r){t.preventDefault(),e.removeFile(r)},onResolveConflict:function(t,r){return e.resolveConflict(t,r)}}),0==this.state.files.length&&React.createElement("div",{className:"field-file-upload__content"},t,React.createElement("em",null,r)),0==this.state.files.length&&"all"==this.props.allowedDirectory&&React.createElement("div",{className:"field-file-upload__controls"},React.createElement(FilterSelect,{key:EE.lang.file_dnd_choose_existing,action:"all"==this.state.directory,center:!0,keepSelectedState:!0,title:EE.lang.file_dnd_choose_directory_btn,placeholder:EE.lang.file_dnd_filter_directories,items:EE.dragAndDrop.uploadDesinations,onSelect:function(t){return e.setDirectory(t)}}))),this.props.showActionButtons&&"all"!=this.props.allowedDirectory&&React.createElement(React.Fragment,null,React.createElement("a",{href:"#",className:"btn action m-link",rel:"modal-file",onClick:function(t){t.preventDefault(),e.chooseExisting(e.state.directory)}},EE.lang.file_dnd_choose_existing)," ",React.createElement("a",{href:"#",className:"btn action m-link",rel:"modal-file",onClick:function(t){t.preventDefault(),e.uploadNew(e.state.directory)}},EE.lang.file_dnd_upload_new)),this.props.showActionButtons&&"all"==this.props.allowedDirectory&&React.createElement("div",{className:"filter-bar filter-bar--inline"},React.createElement(FilterSelect,{key:EE.lang.file_dnd_choose_existing,action:!0,keepSelectedState:!1,title:EE.lang.file_dnd_choose_existing,placeholder:EE.lang.file_dnd_filter_directories,items:EE.dragAndDrop.uploadDesinations,onSelect:function(t){return e.chooseExisting(t)},rel:"modal-file",itemClass:"m-link"}),React.createElement(FilterSelect,{key:EE.lang.file_dnd_upload_new,action:!0,keepSelectedState:!1,title:EE.lang.file_dnd_upload_new,placeholder:EE.lang.file_dnd_filter_directories,items:EE.dragAndDrop.uploadDesinations,onSelect:function(t){return e.uploadNew(t)},rel:"modal-file",itemClass:"m-link"})))}}]),t}(React.Component);_defineProperty(DragAndDropUpload,"defaultProps",{concurrency:5,showActionButtons:!0,filebrowserEndpoint:EE.dragAndDrop.filepickerEndpoint,uploadEndpoint:EE.dragAndDrop.filepickerUploadEndpoint});