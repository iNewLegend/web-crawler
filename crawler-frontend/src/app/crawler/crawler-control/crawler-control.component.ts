import { afterRender, Component, Input } from '@angular/core';

import { FormControl, FormGroup } from "@angular/forms";
import { CrawlerInterfaceWithChildren } from "../crawler.model";
import { ApiCrawlerService } from "../../../api/api.crawler.service";

@Component( {
    selector: 'crawler-control',
    templateUrl: './crawler-control.component.html',
    styleUrls: [ './crawler-control.component.scss' ]
} )

export class CrawlerControlComponent {

    @Input() selectedCrawler: CrawlerInterfaceWithChildren | null = null;

    @Input() shouldDisableCrawlerControl = true;

    crawlerControlForm = new FormGroup( {
        url: new FormControl( '' ),
        depth: new FormControl( 0 ),
    } );

    constructor( private crawler: ApiCrawlerService) {
        afterRender( () => {
            if ( this.selectedCrawler ) {
                this.crawlerControlForm.get( 'url' )?.setValue( this.selectedCrawler.url );
                this.crawlerControlForm.get( 'depth' )?.setValue( this.selectedCrawler.depth || 0 );
            }

            // TODO: Find better solution - When `shouldDisableCrawlerControl` is being changed that disable attribute is not being updated.
            setTimeout( () => {
                if ( this.shouldDisableCrawlerControl ) {
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
        if ( !url.startsWith( 'http://' ) && !url.startsWith( 'https://' ) ) {
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
        this.crawler.createCrawler( url,depth ).subscribe( ( crawler ) => {
            // TODO - Reload page (lazy) - Fix this.
            location.hash = '';
            location.reload();
        } );
    }

    updateCrawler( crawler: CrawlerInterfaceWithChildren ) {
        this.crawler.updateCrawler( crawler.id, this.crawlerControlForm.get( 'depth' )?.value || 0 ).subscribe( ( crawler ) => {
            // TODO - Reload page (lazy) - Fix this.
            location.hash = '';
            location.reload();
        } );
    }
}
