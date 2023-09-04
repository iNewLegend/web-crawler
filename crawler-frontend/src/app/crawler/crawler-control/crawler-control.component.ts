import { afterRender, Component, EventEmitter, Input, Output } from '@angular/core';

import { FormControl, FormGroup } from "@angular/forms";
import { CrawlerInterfaceWithChildren } from "../crawler.model";
import { ApiCrawlerService } from "../../../api/api.crawler.service";

@Component( {
    selector: 'crawler-control',
    templateUrl: './crawler-control.component.html',
    styleUrls: [ './crawler-control.component.scss' ]
} )

export class CrawlerControlComponent {

    @Output() newItemEvent = new EventEmitter<CrawlerInterfaceWithChildren>();

    @Output() updateItemEvent = new EventEmitter<CrawlerInterfaceWithChildren>();

    @Input() selectedItem: CrawlerInterfaceWithChildren | null = null;

    @Input() shouldDisableControl = true;

    crawlerControlForm = new FormGroup( {
        url: new FormControl( '' ),
        depth: new FormControl( 0 ),
    } );

    constructor( private crawler: ApiCrawlerService ) {
        afterRender( () => {
            if ( this.selectedItem ) {
                this.crawlerControlForm.get( 'url' )?.setValue( this.selectedItem.url );
                this.crawlerControlForm.get( 'depth' )?.setValue( this.selectedItem.depth || 0 );
            }

            // TODO: Find better solution - When `shouldDisableCrawlerControl` is being changed that disable attribute is not being updated.
            setTimeout( () => {
                if ( this.shouldDisableControl ) {
                    this.crawlerControlForm.disable();
                } else {
                    this.crawlerControlForm.enable();
                }
            } );
        } );
    }

    onSubmit() {
        const url = this.crawlerControlForm.get( 'url' )?.value || "",
            depth = this.crawlerControlForm.get( 'depth' )?.value || 0;

        // If url not start with http:// or https:// error.
        if ( ! url.startsWith( 'http://' ) && ! url.startsWith( 'https://' ) ) {
            alert( 'Please enter a valid url' );
            return;
        }

        // Check if crawler already exists.
        this.crawler.getCrawlerByUrl( url ).subscribe( ( crawler ) => {
            crawler ?
                this.updateCrawler( crawler ) :
                this.createCrawler( url, depth );
        } );
    }

    createCrawler( url: string, depth = 0 ) {
        this.crawler.createCrawler( url, depth ).subscribe(
            ( crawler ) => this.newItemEvent.emit( crawler )
        );
    }

    updateCrawler( crawler: CrawlerInterfaceWithChildren ) {
        this.crawler.updateCrawler( crawler.id, this.crawlerControlForm.get( 'depth' )?.value || 0 ).subscribe(
            ( crawler ) => this.updateItemEvent.emit( crawler )
        );
    }
}
