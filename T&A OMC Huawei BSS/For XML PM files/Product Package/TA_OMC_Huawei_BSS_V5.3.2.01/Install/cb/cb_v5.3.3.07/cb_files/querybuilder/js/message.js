/*
 * 28/07/2011 SPD1: Querybuilder V2 - A simple message bus (publish/subscribe)
 *  Ext.ux.message.publish method to publish a message
 *  Ext.ux.message.subscribe method to subscribe to a message
 *  Ext.ux.message.unsubscribe method to unsubscribe to a message
 *  For more information see publish/subscribe methods from Dojo tooltkit (http://dojotoolkit.org/)  
 */

Ext.define('Ext.ux.message', {
	
	singleton: true,
		
	// create a dispatcher function
	getDispatcher: function(){
		return function(){
			var ap = Array.prototype, c = arguments.callee, ls = c._listeners, t = c.target, r = t && t.apply(this, arguments), i, lls = [].concat(ls);

			// invoke listeners after target function
			for(i in lls){
				if(!(i in ap)){
					lls[i].apply(this, arguments);
				}
			}
			// return value comes from original target function
			return r;
		};
	},
	// add a listener to an object
	add: function(/*Object*/ source, /*String*/ method, /*Function*/ listener){
		source = source || window;
		// The source method is either null, a dispatcher, or some other function
		var f = source[method];
		// Ensure a dispatcher
		if(!f || !f._listeners){
			var d = this.getDispatcher();
			// original target function is special
			d.target = f;
			// dispatcher holds a list of listeners
			d._listeners = [];
			// redirect source to dispatcher
			f = source[method] = d;
		}
		return f._listeners.push(listener); /*Handle*/
	},
	
	// remove a listener from an object
	remove: function(/*Object*/ source, /*String*/ method, /*Handle*/ handle){
		var f = (source || window)[method];
		// remember that handle is the index+1 (0 is not a valid handle)
		if(f && f._listeners && handle--){
			delete f._listeners[handle];
		}	
	},
	
	// topic publish/subscribe	
	_topics: {},
	
	subscribe: function(/*String*/ topic, /*Object|null*/ context, /*String|Function*/ method){
		//	summary:
		//		Attach a listener to a named topic. The listener function is invoked whenever the
		//		named topic is published (see: Ext.ux.message.publish).
		//		Returns a handle which is needed to unsubscribe this listener.
		//	context:
		//		Scope in which method will be invoked, or null for default scope.
		//	method:
		//		The name of a function in context, or a function reference. This is the function that
		//		is invoked when topic is published.
		//	example:
		//	|	Ext.ux.message.subscribe("alerts", null, function(caption, message){ alert(caption + "\n" + message); });
		//	|	Ext.ux.message.publish("alerts", [ "read this", "hello world" ]);
	
		// support for 2 argument invocation (omitting context) depends on hitch
		return [topic, this.add(this._topics, topic, Ext.bind(method, context))]; /*Handle*/
	},
	
	unsubscribe: function(/*Handle*/ handle){
		//	summary:
		//	 	Remove a topic listener.
		//	handle:
		//	 	The handle returned from a call to subscribe.
		//	example:
		//	|	var alerter = Ext.ux.message.subscribe("alerts", null, function(caption, message){ alert(caption + "\n" + message); };
		//	|	...
		//	|	Ext.ux.message.unsubscribe(alerter);
		if(handle){
			this.remove(this._topics, handle[0], handle[1]);
		}
	},
	
	publish: function(/*String*/ topic, /*Array*/ args){
		//	summary:
		//	 	Invoke all listener method subscribed to topic.
		//	topic:
		//	 	The name of the topic to publish.
		//	args:
		//	 	An array of arguments. The arguments will be applied
		//	 	to each topic subscriber (as first class parameters, via apply).
		//	example:
		//	|	Ext.ux.message.subscribe("alerts", null, function(caption, message){ alert(caption + "\n" + message); };
		//	|	Ext.ux.message.publish("alerts", [ "read this", "hello world" ]);
	
	
	//console.warn(topic, args);
	
		var f = this._topics[topic];
		if(f){
			f.apply(this, args||[]);
		}
	}
});		

