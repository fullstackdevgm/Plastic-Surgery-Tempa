// Javascript for WP 3.5 media uploader
jQuery( function($) {
	// wp.media.controller.ITMediaLibraryAddImage
	// Modified from wp.media.controller.FeaturedImage in wp-includes/js/media-views.js
	// ---------------------------------
	wp.media.controller.ITMediaLibraryAddImage = wp.media.controller.Library.extend({
		defaults: _.defaults({
			id:         'it-medialibrary-add-image',
			filterable: 'uploaded',
			multiple:   false,
			toolbar:    'it-medialibrary-add-image',
			title:      _wpMediaViewsL10n.ITMediaLibraryAddImageTitle,
			priority:   60
		}, wp.media.controller.Library.prototype.defaults ),

		initialize: function() {
			var library, comparator;

			// If we haven't been provided a `library`, create a `Selection`.
			if ( ! this.get('library') )
				this.set( 'library', wp.media.query({ type: 'image' }) );

			wp.media.controller.Library.prototype.initialize.apply( this, arguments );

			library    = this.get('library');
			comparator = library.comparator;

			// Overload the library's comparator to push items that are not in
			// the mirrored query to the front of the aggregate collection.
			library.comparator = function( a, b ) {
				var aInQuery, bInQuery;

				if ( this.mirroring.getByCid ) {
					aInQuery = !! this.mirroring.getByCid( a.cid );
					bInQuery = !! this.mirroring.getByCid( b.cid );
				}
				else {
					aInQuery = !! this.mirroring.get( a.cid );
					bInQuery = !! this.mirroring.get( b.cid );
				}

				if ( ! aInQuery && bInQuery )
					return -1;
				else if ( aInQuery && ! bInQuery )
					return 1;
				else
					return comparator.apply( this, arguments );
			};

			// Add all items in the selection to the library, so any 
			// images that are not initially loaded still appear.
			library.observe( this.get('selection') );
		},

		activate: function() {
			this.updateSelection();
			this.frame.on( 'open', this.updateSelection, this );
			wp.media.controller.Library.prototype.activate.apply( this, arguments );
		},

		deactivate: function() {
			this.frame.off( 'open', this.updateSelection, this );
			wp.media.controller.Library.prototype.deactivate.apply( this, arguments );
		},

		updateSelection: function() {
			var selection = this.get('selection'),
				attachment;

			selection.reset( attachment ? [ attachment ] : [] );
		}
	});

	// ITMediaLibraryAdd Init
	wp.media.itMediaLibraryAddImage = {
		get: function() {
			return;
		},

		set: function( id ) {
			var settings = wp.media.view.settings,
				data = {};

			if ( '-1' != id && '' != id ) {
				data.attachment_id = id;
				data.context = 'pb_medialibrary';
				data = JSON.stringify(new Array(data));

				pb_medialibrary(data);
			}

		},

		frame: function() {
			if ( this._frame )
				return this._frame;

			this._frame = wp.media({
				state: 'it-medialibrary-add-image',
				states: [ new wp.media.controller.ITMediaLibraryAddImage() ]
			});

			this._frame.on( 'toolbar:create:it-medialibrary-add-image', function( toolbar ) {
				this.createSelectToolbar( toolbar, {
					text: _wpMediaViewsL10n.setITMediaLibraryAddImage
				});
			}, this._frame );

			this._frame.state('it-medialibrary-add-image').on( 'select', this.select );
			return this._frame;
		},

		select: function() {
			var settings = wp.media.view.settings,
				selection = this.get('selection').single();

			wp.media.itMediaLibraryAddImage.set( selection ? selection.id : -1 );
		},

		init: function() {
			// Open the content media manager to the 'medialibrary add image' tab when
			// the add new image button is clicked.
			$('.actions').on( 'click', '.add-new-image', function( event ) {
				event.preventDefault();
				// Stop propagation to prevent thickbox from activating.
				event.stopPropagation();

				wp.media.itMediaLibraryAddImage.frame().open();
			});
		}
	};

	$( wp.media.itMediaLibraryAddImage.init );

});
